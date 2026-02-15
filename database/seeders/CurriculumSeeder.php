<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CurriculumItem;
use App\Models\Section;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(int $tenantId): void
    {
        $this->seedLearnPhp($tenantId);
        $this->seedAdvancedLaravel($tenantId);
        $this->seedDatabaseDesign($tenantId);
    }

    private function seedLearnPhp(int $tenantId): void
    {
        $course = Course::where('tenant_id', $tenantId)->where('name', 'Learn PHP')->first();
        if (!$course) {
            return;
        }

        $section1 = Section::create([
            'tenant_id' => $tenantId,
            'course_id' => $course->id,
            'title' => 'PHP Fundamentals',
            'order' => 1,
        ]);

        $quiz1 = CurriculumItem::create([
            'tenant_id' => $tenantId,
            'section_id' => $section1->id,
            'type' => 'quiz',
            'title' => 'PHP Basics Quiz',
            'order' => 1,
            'duration_minutes' => 6,
        ]);

        $quiz1->quizQuestions()->createMany([
            [
                'question' => 'What does PHP stand for?',
                'options' => [
                    'Personal Home Page',
                    'PHP: Hypertext Preprocessor',
                    'Private Hosting Platform',
                    'Programming Hypertext Processor',
                ],
                'correct_answers' => [1],
                'order' => 0,
            ],
            [
                'question' => 'Which symbol is used to denote a variable in PHP?',
                'options' => ['@', '#', '$', '&'],
                'correct_answers' => [2],
                'order' => 1,
            ],
            [
                'question' => 'How do you concatenate strings in PHP?',
                'options' => ['Using +', 'Using .', 'Using &', 'Using ,'],
                'correct_answers' => [1],
                'order' => 2,
            ],
        ]);

        $section2 = Section::create([
            'tenant_id' => $tenantId,
            'course_id' => $course->id,
            'title' => 'Working with Arrays',
            'order' => 2,
        ]);

        $quiz2 = CurriculumItem::create([
            'tenant_id' => $tenantId,
            'section_id' => $section2->id,
            'type' => 'quiz',
            'title' => 'Array Functions Quiz',
            'order' => 1,
            'duration_minutes' => 8,
        ]);

        $quiz2->quizQuestions()->createMany([
            [
                'question' => 'Which function is used to count elements in an array?',
                'options' => ['count()', 'length()', 'size()', 'sizeof()'],
                'correct_answers' => [0, 3],
                'order' => 0,
            ],
            [
                'question' => 'How do you add an element to the end of an array?',
                'options' => [
                    'array_add()',
                    'array_push()',
                    'array_append()',
                    '$array[] = $value',
                ],
                'correct_answers' => [1, 3],
                'order' => 1,
            ],
            [
                'question' => 'Which function removes and returns the last element of an array?',
                'options' => ['array_pop()', 'array_remove()', 'array_pull()', 'array_delete()'],
                'correct_answers' => [0],
                'order' => 2,
            ],
            [
                'question' => 'What does in_array() return?',
                'options' => ['An integer', 'A boolean', 'An array', 'A string'],
                'correct_answers' => [1],
                'order' => 3,
            ],
        ]);

        $section3 = Section::create([
            'tenant_id' => $tenantId,
            'course_id' => $course->id,
            'title' => 'Object-Oriented PHP',
            'order' => 3,
        ]);

        $quiz3 = CurriculumItem::create([
            'tenant_id' => $tenantId,
            'section_id' => $section3->id,
            'type' => 'quiz',
            'title' => 'OOP Concepts Quiz',
            'order' => 1,
            'duration_minutes' => 10,
        ]);

        $quiz3->quizQuestions()->createMany([
            [
                'question' => 'Which keyword is used to create a class in PHP?',
                'options' => ['class', 'object', 'new', 'function'],
                'correct_answers' => [0],
                'order' => 0,
            ],
            [
                'question' => 'What are the main principles of OOP? (Select all that apply)',
                'options' => [
                    'Encapsulation',
                    'Inheritance',
                    'Polymorphism',
                    'Abstraction',
                ],
                'correct_answers' => [0, 1, 2, 3],
                'order' => 1,
            ],
            [
                'question' => 'Which visibility modifier makes a property accessible only within the class?',
                'options' => ['public', 'protected', 'private', 'internal'],
                'correct_answers' => [2],
                'order' => 2,
            ],
            [
                'question' => 'What is the purpose of a constructor?',
                'options' => [
                    'To destroy an object',
                    'To initialize an object',
                    'To clone an object',
                    'To compare objects',
                ],
                'correct_answers' => [1],
                'order' => 3,
            ],
            [
                'question' => 'Which keyword is used to refer to the current object instance?',
                'options' => ['self', 'this', 'parent', 'static'],
                'correct_answers' => [1],
                'order' => 4,
            ],
        ]);
    }

    private function seedAdvancedLaravel(int $tenantId): void
    {
        $course = Course::where('tenant_id', $tenantId)->where('name', 'Advanced Laravel')->first();
        if (!$course) {
            return;
        }

        $section1 = Section::create([
            'tenant_id' => $tenantId,
            'course_id' => $course->id,
            'title' => 'Eloquent ORM Mastery',
            'order' => 1,
        ]);

        $quiz1 = CurriculumItem::create([
            'tenant_id' => $tenantId,
            'section_id' => $section1->id,
            'type' => 'quiz',
            'title' => 'Eloquent Relationships Quiz',
            'order' => 1,
            'duration_minutes' => 10,
        ]);

        $quiz1->quizQuestions()->createMany([
            [
                'question' => 'Which relationship method would you use for a one-to-many relationship?',
                'options' => ['belongsTo()', 'hasMany()', 'hasOne()', 'belongsToMany()'],
                'correct_answers' => [1],
                'order' => 0,
            ],
            [
                'question' => 'What does the belongsToMany() method define?',
                'options' => [
                    'One-to-one relationship',
                    'One-to-many relationship',
                    'Many-to-many relationship',
                    'Polymorphic relationship',
                ],
                'correct_answers' => [2],
                'order' => 1,
            ],
            [
                'question' => 'Which methods can be used for eager loading? (Select all that apply)',
                'options' => ['with()', 'load()', 'eager()', 'include()'],
                'correct_answers' => [0, 1],
                'order' => 2,
            ],
            [
                'question' => 'What is the N+1 query problem?',
                'options' => [
                    'Too many database connections',
                    'Multiple queries executed when one would suffice',
                    'Incorrect SQL syntax',
                    'Missing foreign keys',
                ],
                'correct_answers' => [1],
                'order' => 3,
            ],
            [
                'question' => 'Which method would you use to define an inverse one-to-many relationship?',
                'options' => ['hasMany()', 'belongsTo()', 'hasOne()', 'manyToOne()'],
                'correct_answers' => [1],
                'order' => 4,
            ],
        ]);

        $section2 = Section::create([
            'course_id' => $course->id,
            'title' => 'Service Container & Dependency Injection',
            'order' => 2,
        ]);

        $quiz2 = CurriculumItem::create([
            'section_id' => $section2->id,
            'type' => 'quiz',
            'title' => 'Service Container Quiz',
            'order' => 1,
            'duration_minutes' => 8,
        ]);

        $quiz2->quizQuestions()->createMany([
            [
                'question' => 'What is the Laravel Service Container?',
                'options' => [
                    'A database connection pool',
                    'A dependency injection container',
                    'A caching mechanism',
                    'A routing system',
                ],
                'correct_answers' => [1],
                'order' => 0,
            ],
            [
                'question' => 'Which methods can bind services to the container? (Select all that apply)',
                'options' => ['bind()', 'singleton()', 'instance()', 'register()'],
                'correct_answers' => [0, 1, 2],
                'order' => 1,
            ],
            [
                'question' => 'What is the difference between bind() and singleton()?',
                'options' => [
                    'bind() creates a new instance each time, singleton() reuses the same instance',
                    'singleton() is faster than bind()',
                    'bind() is deprecated',
                    'There is no difference',
                ],
                'correct_answers' => [0],
                'order' => 2,
            ],
            [
                'question' => 'How does Laravel resolve dependencies automatically?',
                'options' => [
                    'Through configuration files',
                    'Through reflection and type hinting',
                    'Through manual registration',
                    'Through database lookups',
                ],
                'correct_answers' => [1],
                'order' => 3,
            ],
        ]);

        $section3 = Section::create([
            'course_id' => $course->id,
            'title' => 'Testing in Laravel',
            'order' => 3,
        ]);

        $quiz3 = CurriculumItem::create([
            'section_id' => $section3->id,
            'type' => 'quiz',
            'title' => 'Laravel Testing Quiz',
            'order' => 1,
            'duration_minutes' => 8,
        ]);

        $quiz3->quizQuestions()->createMany([
            [
                'question' => 'Which testing framework does Laravel use by default?',
                'options' => ['Jest', 'PHPUnit', 'Mocha', 'Jasmine'],
                'correct_answers' => [1],
                'order' => 0,
            ],
            [
                'question' => 'What is the purpose of the RefreshDatabase trait?',
                'options' => [
                    'To optimize database queries',
                    'To reset the database between tests',
                    'To cache database results',
                    'To backup the database',
                ],
                'correct_answers' => [1],
                'order' => 1,
            ],
            [
                'question' => 'Which assertions are available in Laravel HTTP tests? (Select all that apply)',
                'options' => [
                    'assertStatus()',
                    'assertSee()',
                    'assertDatabaseHas()',
                    'assertRedirect()',
                ],
                'correct_answers' => [0, 1, 2, 3],
                'order' => 2,
            ],
            [
                'question' => 'What is a Feature Test in Laravel?',
                'options' => [
                    'A test that tests a single unit of code',
                    'A test that tests multiple parts working together',
                    'A performance benchmark',
                    'A security audit',
                ],
                'correct_answers' => [1],
                'order' => 3,
            ],
        ]);
    }

    private function seedDatabaseDesign(int $tenantId): void
    {
        $course = Course::where('tenant_id', $tenantId)->where('name', 'Database Design')->first();
        if (!$course) {
            return;
        }

        $section1 = Section::create([
            'tenant_id' => $tenantId,
            'course_id' => $course->id,
            'title' => 'Normalization & Normal Forms',
            'order' => 1,
        ]);

        $quiz1 = CurriculumItem::create([
            'tenant_id' => $tenantId,
            'section_id' => $section1->id,
            'type' => 'quiz',
            'title' => 'Database Normalization Quiz',
            'order' => 1,
            'duration_minutes' => 10,
        ]);

        $quiz1->quizQuestions()->createMany([
            [
                'question' => 'What is the primary goal of database normalization?',
                'options' => [
                    'To increase database size',
                    'To eliminate data redundancy and improve data integrity',
                    'To make queries slower',
                    'To add more tables',
                ],
                'correct_answers' => [1],
                'order' => 0,
            ],
            [
                'question' => 'What does First Normal Form (1NF) require?',
                'options' => [
                    'All attributes must be atomic (no repeating groups)',
                    'All non-key attributes must depend on the primary key',
                    'No transitive dependencies',
                    'Multiple values per cell are allowed',
                ],
                'correct_answers' => [0],
                'order' => 1,
            ],
            [
                'question' => 'Which normal forms eliminate partial dependencies? (Select all that apply)',
                'options' => ['1NF', '2NF', '3NF', 'BCNF'],
                'correct_answers' => [1, 2, 3],
                'order' => 2,
            ],
            [
                'question' => 'What is a transitive dependency?',
                'options' => [
                    'When A depends on B, and B depends on C, so A depends on C',
                    'When two tables are related',
                    'When a foreign key references a primary key',
                    'When data is duplicated',
                ],
                'correct_answers' => [0],
                'order' => 3,
            ],
            [
                'question' => 'When might denormalization be appropriate?',
                'options' => [
                    'Never, it should always be avoided',
                    'For read-heavy applications where query performance is critical',
                    'When you want to save disk space',
                    'In all production databases',
                ],
                'correct_answers' => [1],
                'order' => 4,
            ],
        ]);

        $section2 = Section::create([
            'course_id' => $course->id,
            'title' => 'Indexes & Query Optimization',
            'order' => 2,
        ]);

        $quiz2 = CurriculumItem::create([
            'section_id' => $section2->id,
            'type' => 'quiz',
            'title' => 'Database Indexing Quiz',
            'order' => 1,
            'duration_minutes' => 10,
        ]);

        $quiz2->quizQuestions()->createMany([
            [
                'question' => 'What is the primary purpose of a database index?',
                'options' => [
                    'To store data',
                    'To speed up data retrieval operations',
                    'To enforce data integrity',
                    'To compress data',
                ],
                'correct_answers' => [1],
                'order' => 0,
            ],
            [
                'question' => 'Which statements about indexes are true? (Select all that apply)',
                'options' => [
                    'Indexes speed up SELECT queries',
                    'Indexes can slow down INSERT, UPDATE, DELETE operations',
                    'Every column should have an index',
                    'Indexes consume disk space',
                ],
                'correct_answers' => [0, 1, 3],
                'order' => 1,
            ],
            [
                'question' => 'What is a composite index?',
                'options' => [
                    'An index on a single column',
                    'An index on multiple columns',
                    'An index that stores data',
                    'An index on a foreign key',
                ],
                'correct_answers' => [1],
                'order' => 2,
            ],
            [
                'question' => 'What is a covering index?',
                'options' => [
                    'An index that includes all columns needed for a query',
                    'The primary key index',
                    'An index on every column',
                    'A backup index',
                ],
                'correct_answers' => [0],
                'order' => 3,
            ],
            [
                'question' => 'Which type of index is automatically created for a primary key?',
                'options' => [
                    'Secondary index',
                    'Clustered index',
                    'Full-text index',
                    'Composite index',
                ],
                'correct_answers' => [1],
                'order' => 4,
            ],
        ]);

        $section3 = Section::create([
            'course_id' => $course->id,
            'title' => 'Transactions & ACID Properties',
            'order' => 3,
        ]);

        $quiz3 = CurriculumItem::create([
            'section_id' => $section3->id,
            'type' => 'quiz',
            'title' => 'Database Transactions Quiz',
            'order' => 1,
            'duration_minutes' => 8,
        ]);

        $quiz3->quizQuestions()->createMany([
            [
                'question' => 'What does ACID stand for in database transactions?',
                'options' => [
                    'Atomic, Consistent, Isolated, Durable',
                    'Always, Complete, Immediate, Delayed',
                    'All, Clear, Independent, Done',
                    'Active, Closed, Inactive, Draft',
                ],
                'correct_answers' => [0],
                'order' => 0,
            ],
            [
                'question' => 'What does Atomicity guarantee?',
                'options' => [
                    'Data is always consistent',
                    'All operations in a transaction succeed or all fail',
                    'Transactions are isolated from each other',
                    'Changes persist after commit',
                ],
                'correct_answers' => [1],
                'order' => 1,
            ],
            [
                'question' => 'Which isolation level prevents dirty reads but allows non-repeatable reads?',
                'options' => [
                    'Read Uncommitted',
                    'Read Committed',
                    'Repeatable Read',
                    'Serializable',
                ],
                'correct_answers' => [1],
                'order' => 2,
            ],
            [
                'question' => 'What is a deadlock?',
                'options' => [
                    'A permanently locked record',
                    'Two or more transactions waiting for each other to release locks',
                    'A failed transaction',
                    'A corrupted database',
                ],
                'correct_answers' => [1],
                'order' => 3,
            ],
        ]);
    }
}
