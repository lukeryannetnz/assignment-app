<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use InvalidArgumentException;

class OrganizationHierarchyTemplateService
{
    /**
     * @return list<array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     csv_rows: list<string>
     * }>
     */
    public function listTemplates(): array
    {
        return [
            [
                'key' => 'regional-divisions',
                'name' => 'Regional Divisions',
                'description' => 'One business unit per region with departments'
                    . ' and implementation teams beneath each region.',
                'csv_rows' => [
                    'row_key,parent_row_key,node_type,name',
                    'north-america,,business_unit,North America',
                    'engineering,north-america,department,Engineering',
                    'platform,engineering,team,Platform Team',
                ],
            ],
            [
                'key' => 'centralized-functions',
                'name' => 'Centralized Functions',
                'description' => 'Shared service departments grouped under one corporate services business unit.',
                'csv_rows' => [
                    'row_key,parent_row_key,node_type,name',
                    'corporate-services,,business_unit,Corporate Services',
                    'people-ops,corporate-services,department,People Operations',
                    'enablement,people-ops,team,Learning Enablement',
                    'finance,corporate-services,department,Finance',
                    'planning,finance,team,Planning and Analysis',
                ],
            ],
            [
                'key' => 'school-network',
                'name' => 'School Network',
                'description' => 'Education operators with campuses as business units'
                    . ' and teaching teams grouped by academic area.',
                'csv_rows' => [
                    'row_key,parent_row_key,node_type,name',
                    'auckland-campus,,business_unit,Auckland Campus',
                    'science,auckland-campus,department,Science Faculty',
                    'lab-instruction,science,team,Lab Instruction Team',
                    'wellington-campus,,business_unit,Wellington Campus',
                    'humanities,wellington-campus,department,Humanities Faculty',
                    'curriculum-design,humanities,team,Curriculum Design Team',
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     csv_rows: list<string>
     * }
     */
    public function findTemplate(string $templateKey): array
    {
        foreach ($this->listTemplates() as $template) {
            if ($template['key'] === $templateKey) {
                return $template;
            }
        }

        throw new InvalidArgumentException(sprintf('Unknown hierarchy template [%s].', $templateKey));
    }

    public function csvFor(string $templateKey): string
    {
        return implode("\n", $this->findTemplate($templateKey)['csv_rows']) . "\n";
    }
}
