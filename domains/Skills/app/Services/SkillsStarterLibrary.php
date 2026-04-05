<?php

declare(strict_types=1);

namespace App\Domains\Skills\Services;

use App\Domains\Skills\Data\ProficiencyBand;
use App\Domains\Skills\Data\SkillImportance;

class SkillsStarterLibrary
{
    /**
     * @return list<array<string, mixed>>
     */
    public function families(): array
    {
        return [
            [
                'family' => 'Software Development',
                'description' => 'Curated starter roles for engineering teams and technical leadership.',
                'roles' => [
                    [
                        'name' => 'Software Engineer',
                        'description' => 'Builds product features, maintains quality, and ships reliable code.',
                        'skills' => [
                            $this->skill(
                                'PHP and Laravel',
                                'Backend Engineering',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Core delivery stack for application work.',
                                1,
                            ),
                            $this->skill(
                                'Testing discipline',
                                'Engineering Quality',
                                SkillImportance::Core,
                                ProficiencyBand::Proficient,
                                'Covers route-driven workflows and regression prevention.',
                                2,
                            ),
                            $this->skill(
                                'API design',
                                'Backend Engineering',
                                SkillImportance::Core,
                                ProficiencyBand::Proficient,
                                'Supports explicit contracts and downstream integration.',
                                3,
                            ),
                            $this->skill(
                                'Code review collaboration',
                                'Team Delivery',
                                SkillImportance::Supporting,
                                ProficiencyBand::Proficient,
                                'Improves code quality and shared ownership.',
                                4,
                            ),
                        ],
                    ],
                    [
                        'name' => 'Senior Software Engineer',
                        'description' => 'Owns design tradeoffs and delivery quality across larger problem spaces.',
                        'skills' => [
                            $this->skill(
                                'System design',
                                'Architecture',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Needed for multi-service and high-scale feature design.',
                                1,
                            ),
                            $this->skill(
                                'Architecture tradeoffs',
                                'Architecture',
                                SkillImportance::Core,
                                ProficiencyBand::Advanced,
                                'Balances velocity, maintainability, and operational risk.',
                                2,
                            ),
                            $this->skill(
                                'Mentoring',
                                'People Leadership',
                                SkillImportance::Core,
                                ProficiencyBand::Proficient,
                                'Raises engineering standards across the team.',
                                3,
                            ),
                            $this->skill(
                                'Delivery ownership',
                                'Team Delivery',
                                SkillImportance::Supporting,
                                ProficiencyBand::Proficient,
                                'Keeps execution moving through ambiguity.',
                                4,
                            ),
                        ],
                    ],
                    [
                        'name' => 'Engineering Manager',
                        'description' => 'Guides team execution, planning, and technical quality at a team level.',
                        'skills' => [
                            $this->skill(
                                'Technical planning',
                                'Engineering Management',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Translates strategy into executable engineering plans.',
                                1,
                            ),
                            $this->skill(
                                'People leadership',
                                'People Leadership',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Creates clarity, accountability, and team health.',
                                2,
                            ),
                            $this->skill(
                                'Feedback and coaching',
                                'People Leadership',
                                SkillImportance::Core,
                                ProficiencyBand::Advanced,
                                'Develops capability and performance sustainably.',
                                3,
                            ),
                            $this->skill(
                                'Execution rhythm',
                                'Engineering Management',
                                SkillImportance::Core,
                                ProficiencyBand::Proficient,
                                'Maintains delivery predictability and follow-through.',
                                4,
                            ),
                        ],
                    ],
                ],
            ],
            [
                'family' => 'Product Management',
                'description' => 'Starter roles for product discovery, delivery, and strategy.',
                'roles' => [
                    [
                        'name' => 'Product Manager',
                        'description' => 'Owns discovery, prioritization, and '
                            . 'stakeholder alignment for a product area.',
                        'skills' => [
                            $this->skill(
                                'Product discovery',
                                'Product Craft',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Finds the right problems and validates demand.',
                                1,
                            ),
                            $this->skill(
                                'Roadmapping',
                                'Product Craft',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Turns strategy into sequenced execution.',
                                2,
                            ),
                            $this->skill(
                                'Stakeholder management',
                                'Communication',
                                SkillImportance::Core,
                                ProficiencyBand::Advanced,
                                'Aligns cross-functional teams and decision-makers.',
                                3,
                            ),
                            $this->skill(
                                'Metrics and experiments',
                                'Analytics',
                                SkillImportance::Core,
                                ProficiencyBand::Proficient,
                                'Measures progress and improves decisions.',
                                4,
                            ),
                            $this->skill(
                                'Backlog prioritization',
                                'Product Craft',
                                SkillImportance::Supporting,
                                ProficiencyBand::Proficient,
                                'Maintains day-to-day delivery focus.',
                                5,
                            ),
                        ],
                    ],
                    [
                        'name' => 'Senior Product Manager',
                        'description' => 'Owns broader product outcomes across larger, more ambiguous domains.',
                        'skills' => [
                            $this->skill(
                                'Portfolio thinking',
                                'Strategy',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Connects individual product bets to portfolio outcomes.',
                                1,
                            ),
                            $this->skill(
                                'Outcome framing',
                                'Strategy',
                                SkillImportance::Critical,
                                ProficiencyBand::Advanced,
                                'Keeps teams focused on measurable results.',
                                2,
                            ),
                            $this->skill(
                                'Decision communication',
                                'Communication',
                                SkillImportance::Core,
                                ProficiencyBand::Advanced,
                                'Explains choices clearly to executives and partners.',
                                3,
                            ),
                            $this->skill(
                                'Cross-functional influence',
                                'Leadership',
                                SkillImportance::Supporting,
                                ProficiencyBand::Proficient,
                                'Moves complex work without formal authority.',
                                4,
                            ),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function skill(
        string $name,
        string $family,
        SkillImportance $importance,
        ProficiencyBand $targetProficiency,
        string $rationaleNote,
        int $sortOrder,
    ): array {
        return [
            'name' => $name,
            'family' => $family,
            'importance' => $importance,
            'target_proficiency' => $targetProficiency,
            'rationale_note' => $rationaleNote,
            'sort_order' => $sortOrder,
        ];
    }
}
