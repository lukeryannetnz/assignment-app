<?php

declare(strict_types=1);

namespace App\Domains\Curriculum\Services;

use App\Domains\Curriculum\Data\CurriculumCourseData;
use App\Domains\Curriculum\Data\CurriculumItemData;
use App\Domains\Curriculum\Data\CurriculumItemType;
use App\Domains\Curriculum\Data\CurriculumSectionData;
use App\Domains\Curriculum\Data\QuizItemInputData;
use App\Domains\Curriculum\Data\QuizQuestionData;
use App\Domains\Curriculum\Data\QuizQuestionInputData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CurriculumService
{
    public function findCourse(int $tenantId, int $courseId): CurriculumCourseData
    {
        /** @var object{id: int, tenant_id: int, name: string}|null $row */
        $row = DB::selectOne(
            'SELECT id, tenant_id, name
             FROM courses
             WHERE tenant_id = ?
               AND id = ?
             LIMIT 1',
            [$tenantId, $courseId],
        );

        if ($row === null) {
            throw new NotFoundHttpException("Course {$courseId} was not found.");
        }

        return new CurriculumCourseData(
            id: (int) $row->id,
            tenant_id: (int) $row->tenant_id,
            name: (string) $row->name,
        );
    }

    /**
     * @return Collection<int, CurriculumSectionData>
     */
    public function listSections(int $tenantId, int $courseId): Collection
    {
        $course = $this->findCourse($tenantId, $courseId);

        /** @var list<object{id: int, tenant_id: int, course_id: int, title: string, order: int|string}> $rows */
        $rows = DB::select(
            'SELECT id, tenant_id, course_id, title, `order`
             FROM sections
             WHERE tenant_id = ?
               AND course_id = ?
             ORDER BY `order` ASC, id ASC',
            [$tenantId, $courseId],
        );

        return collect($rows)->map(
            fn (object $row): CurriculumSectionData => $this->mapSectionRowWithItems($course, $row),
        );
    }

    public function findSection(int $tenantId, int $courseId, int $sectionId): CurriculumSectionData
    {
        $course = $this->findCourse($tenantId, $courseId);
        $row = $this->loadSectionRow($tenantId, $courseId, $sectionId);

        return $this->mapSectionRow($course, $row, collect());
    }

    public function createSection(int $tenantId, int $courseId, string $title, int $order): CurriculumSectionData
    {
        $this->findCourse($tenantId, $courseId);

        DB::insert(
            'INSERT INTO sections (tenant_id, course_id, title, `order`, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $courseId, $title, $order, now(), now()],
        );

        return $this->findLatestSection($tenantId, $courseId, $title, $order);
    }

    public function updateSection(
        int $tenantId,
        int $courseId,
        int $sectionId,
        string $title,
        int $order,
    ): CurriculumSectionData {
        $this->loadSectionRow($tenantId, $courseId, $sectionId);

        $affected = DB::update(
            'UPDATE sections
             SET title = ?, `order` = ?, updated_at = ?
             WHERE tenant_id = ?
               AND course_id = ?
               AND id = ?',
            [$title, $order, now(), $tenantId, $courseId, $sectionId],
        );

        if ($affected === 0) {
            throw new NotFoundHttpException("Section {$sectionId} was not found.");
        }

        return $this->findSection($tenantId, $courseId, $sectionId);
    }

    public function deleteSection(int $tenantId, int $courseId, int $sectionId): void
    {
        $this->loadSectionRow($tenantId, $courseId, $sectionId);

        $affected = DB::delete(
            'DELETE FROM sections
             WHERE tenant_id = ?
               AND course_id = ?
               AND id = ?',
            [$tenantId, $courseId, $sectionId],
        );

        if ($affected === 0) {
            throw new NotFoundHttpException("Section {$sectionId} was not found.");
        }
    }

    public function findSectionForItem(int $tenantId, int $sectionId): CurriculumSectionData
    {
        /** @var object{id: int, tenant_id: int, course_id: int, title: string, order: int|string, course_name: string}|null $row */
        $row = DB::selectOne(
            'SELECT
                s.id,
                s.tenant_id,
                s.course_id,
                s.title,
                s.`order`,
                c.name AS course_name
             FROM sections s
             INNER JOIN courses c
                ON c.id = s.course_id
               AND c.tenant_id = s.tenant_id
             WHERE s.tenant_id = ?
               AND s.id = ?
             LIMIT 1',
            [$tenantId, $sectionId],
        );

        if ($row === null) {
            throw new NotFoundHttpException("Section {$sectionId} was not found.");
        }

        $course = new CurriculumCourseData(
            id: (int) $row->course_id,
            tenant_id: (int) $row->tenant_id,
            name: (string) $row->course_name,
        );

        return $this->mapSectionRow(
            $course,
            $row,
            $this->loadItemsForSection($tenantId, $sectionId),
        );
    }

    public function createQuizItem(int $tenantId, int $sectionId, QuizItemInputData $input): CurriculumItemData
    {
        $this->findSectionForItem($tenantId, $sectionId);

        $itemId = DB::transaction(function () use ($tenantId, $sectionId, $input): int {
            DB::insert(
                'INSERT INTO curriculum_items
                    (tenant_id, section_id, type, title, duration_minutes, `order`, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantId,
                    $sectionId,
                    CurriculumItemType::Quiz->value,
                    $input->title,
                    $input->questions->count() * 2,
                    $input->order,
                    now(),
                    now(),
                ],
            );

            $itemId = $this->lastInsertId();

            /** @var QuizQuestionInputData $question */
            foreach ($input->questions as $index => $question) {
                DB::insert(
                    'INSERT INTO quiz_questions
                        (tenant_id, curriculum_item_id, question, options, correct_answers,
                         `order`, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tenantId,
                        $itemId,
                        $question->question,
                        $this->encodeJson($question->options),
                        $this->encodeJson($question->correctAnswers),
                        $index,
                        now(),
                        now(),
                    ],
                );
            }

            return $itemId;
        });

        return $this->findItem($tenantId, $sectionId, $itemId);
    }

    public function updateQuizItem(
        int $tenantId,
        int $sectionId,
        int $itemId,
        QuizItemInputData $input,
    ): CurriculumItemData {
        $this->loadItemRow($tenantId, $sectionId, $itemId);

        DB::transaction(function () use ($tenantId, $sectionId, $itemId, $input): void {
            DB::update(
                'UPDATE curriculum_items
                 SET title = ?, duration_minutes = ?, `order` = ?, updated_at = ?
                 WHERE tenant_id = ?
                   AND section_id = ?
                   AND id = ?',
                [
                    $input->title,
                    $input->questions->count() * 2,
                    $input->order,
                    now(),
                    $tenantId,
                    $sectionId,
                    $itemId,
                ],
            );

            DB::delete(
                'DELETE FROM quiz_questions
                 WHERE tenant_id = ?
                   AND curriculum_item_id = ?',
                [$tenantId, $itemId],
            );

            /** @var QuizQuestionInputData $question */
            foreach ($input->questions as $index => $question) {
                DB::insert(
                    'INSERT INTO quiz_questions
                        (tenant_id, curriculum_item_id, question, options, correct_answers,
                         `order`, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $tenantId,
                        $itemId,
                        $question->question,
                        $this->encodeJson($question->options),
                        $this->encodeJson($question->correctAnswers),
                        $index,
                        now(),
                        now(),
                    ],
                );
            }
        });

        return $this->findItem($tenantId, $sectionId, $itemId);
    }

    public function deleteQuizItem(int $tenantId, int $sectionId, int $itemId): void
    {
        $this->loadItemRow($tenantId, $sectionId, $itemId);

        $affected = DB::delete(
            'DELETE FROM curriculum_items
             WHERE tenant_id = ?
               AND section_id = ?
               AND id = ?',
            [$tenantId, $sectionId, $itemId],
        );

        if ($affected === 0) {
            throw new NotFoundHttpException("Curriculum item {$itemId} was not found.");
        }
    }

    public function findItem(int $tenantId, int $sectionId, int $itemId): CurriculumItemData
    {
        $row = $this->loadItemRow($tenantId, $sectionId, $itemId);
        $questions = $this->loadQuestionsForItem($tenantId, $itemId);

        return $this->mapItemRow($row, $questions);
    }

    /**
     * @param object{id: int, tenant_id: int, course_id: int, title: string, order: int|string} $row
     * @param Collection<int, CurriculumItemData> $items
     * @return CurriculumSectionData
     */
    private function mapSectionRow(
        CurriculumCourseData $course,
        object $row,
        Collection $items,
    ): CurriculumSectionData {
        return new CurriculumSectionData(
            id: (int) $row->id,
            tenant_id: (int) $row->tenant_id,
            course_id: (int) $row->course_id,
            title: (string) $row->title,
            order: (int) $row->order,
            course: $course,
            curriculumItems: $items,
        );
    }

    /**
     * @param object{id: int, tenant_id: int, course_id: int, title: string, order: int|string} $row
     */
    private function mapSectionRowWithItems(CurriculumCourseData $course, object $row): CurriculumSectionData
    {
        return $this->mapSectionRow(
            $course,
            $row,
            $this->loadItemsForSection((int) $row->tenant_id, (int) $row->id),
        );
    }

    /**
     * @param object{
     *   id: int,
     *   tenant_id: int,
     *   section_id: int,
     *   type: string,
     *   title: string,
     *   duration_minutes: int|string,
     *   order: int|string,
     *   video_path: string|null,
     *   assignment_content: string|null
     * } $row
     * @param Collection<int, QuizQuestionData> $questions
     */
    private function mapItemRow(object $row, Collection $questions): CurriculumItemData
    {
        return new CurriculumItemData(
            id: (int) $row->id,
            tenant_id: (int) $row->tenant_id,
            section_id: (int) $row->section_id,
            type: CurriculumItemType::from((string) $row->type),
            title: (string) $row->title,
            duration_minutes: (int) $row->duration_minutes,
            order: (int) $row->order,
            video_path: $row->video_path !== null ? (string) $row->video_path : null,
            assignment_content: $row->assignment_content !== null ? (string) $row->assignment_content : null,
            quizQuestions: $questions,
        );
    }

    /**
     * @return Collection<int, CurriculumItemData>
     */
    private function loadItemsForSection(int $tenantId, int $sectionId): Collection
    {
        /** @var list<object{id: int, tenant_id: int, section_id: int, type: string, title: string, duration_minutes: int|string, order: int|string, video_path: string|null, assignment_content: string|null}> $rows */
        $rows = DB::select(
            'SELECT id, tenant_id, section_id, type, title, duration_minutes, `order`, video_path, assignment_content
             FROM curriculum_items
             WHERE tenant_id = ?
               AND section_id = ?
             ORDER BY `order` ASC, id ASC',
            [$tenantId, $sectionId],
        );

        return collect($rows)->map(
            fn (object $row): CurriculumItemData => $this->mapItemRow(
                $row,
                $this->loadQuestionsForItem($tenantId, (int) $row->id),
            ),
        );
    }

    /**
     * @return Collection<int, QuizQuestionData>
     */
    private function loadQuestionsForItem(int $tenantId, int $itemId): Collection
    {
        /** @var list<object{id: int, tenant_id: int, curriculum_item_id: int, question: string, options: string, correct_answers: string, order: int|string}> $rows */
        $rows = DB::select(
            'SELECT id, tenant_id, curriculum_item_id, question, options, correct_answers, `order`
             FROM quiz_questions
             WHERE tenant_id = ?
               AND curriculum_item_id = ?
             ORDER BY `order` ASC, id ASC',
            [$tenantId, $itemId],
        );

        return collect($rows)->map(function (object $row): QuizQuestionData {
            return new QuizQuestionData(
                id: (int) $row->id,
                tenant_id: (int) $row->tenant_id,
                curriculum_item_id: (int) $row->curriculum_item_id,
                question: (string) $row->question,
                options: $this->decodeJsonList($row->options),
                correctAnswers: array_map('intval', $this->decodeJsonList($row->correct_answers)),
                order: (int) $row->order,
            );
        });
    }

    /**
     * @return object{id: int, tenant_id: int, course_id: int, title: string, order: int|string}
     */
    private function loadSectionRow(int $tenantId, int $courseId, int $sectionId): object
    {
        /** @var object{id: int, tenant_id: int, course_id: int, title: string, order: int|string}|null $row */
        $row = DB::selectOne(
            'SELECT id, tenant_id, course_id, title, `order`
             FROM sections
             WHERE tenant_id = ?
               AND course_id = ?
               AND id = ?
             LIMIT 1',
            [$tenantId, $courseId, $sectionId],
        );

        if ($row === null) {
            throw new NotFoundHttpException("Section {$sectionId} was not found.");
        }

        return $row;
    }

    /**
     * @return object{
     *   id: int,
     *   tenant_id: int,
     *   section_id: int,
     *   type: string,
     *   title: string,
     *   duration_minutes: int|string,
     *   order: int|string,
     *   video_path: string|null,
     *   assignment_content: string|null
     * }
     */
    private function loadItemRow(int $tenantId, int $sectionId, int $itemId): object
    {
        /** @var object{id: int, tenant_id: int, section_id: int, type: string, title: string, duration_minutes: int|string, order: int|string, video_path: string|null, assignment_content: string|null}|null $row */
        $row = DB::selectOne(
            'SELECT id, tenant_id, section_id, type, title, duration_minutes, `order`, video_path, assignment_content
             FROM curriculum_items
             WHERE tenant_id = ?
               AND section_id = ?
               AND id = ?
             LIMIT 1',
            [$tenantId, $sectionId, $itemId],
        );

        if ($row === null) {
            throw new NotFoundHttpException("Curriculum item {$itemId} was not found.");
        }

        return $row;
    }

    /**
     * @return CurriculumSectionData
     */
    private function findLatestSection(int $tenantId, int $courseId, string $title, int $order): CurriculumSectionData
    {
        /** @var object{id: int, tenant_id: int, course_id: int, title: string, order: int|string, course_name: string}|null $row */
        $row = DB::selectOne(
            'SELECT s.id, s.tenant_id, s.course_id, s.title, s.`order`, c.name AS course_name
             FROM sections s
             INNER JOIN courses c ON c.id = s.course_id AND c.tenant_id = s.tenant_id
             WHERE s.tenant_id = ?
               AND s.course_id = ?
               AND s.title = ?
               AND s.`order` = ?
             ORDER BY s.id DESC
             LIMIT 1',
            [$tenantId, $courseId, $title, $order],
        );

        if ($row === null) {
            throw new RuntimeException('Unable to resolve newly created section.');
        }

        $course = new CurriculumCourseData(
            id: (int) $row->course_id,
            tenant_id: (int) $row->tenant_id,
            name: (string) $row->course_name,
        );

        return $this->mapSectionRow($course, $row, collect());
    }

    /**
     * @param list<string>|list<int> $values
     */
    private function encodeJson(array $values): string
    {
        return json_encode($values, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    private function decodeJsonList(string $json): array
    {
        /** @var list<string>|list<int> $values */
        $values = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return array_map(static fn (mixed $value): string => (string) $value, $values);
    }

    private function lastInsertId(): int
    {
        return (int) DB::getPdo()->lastInsertId();
    }
}
