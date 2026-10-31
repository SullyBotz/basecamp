<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Job::create([
            'title' => 'Frontend Developer',
            'company' => 'PT. Maju Mundur',
            'location' => 'Jakarta',
            'type' => 'Full-time',
        ]);

        \App\Models\Job::create([
            'title' => 'Data Analyst',
            'company' => 'Tech Corp',
            'location' => 'Remote',
            'type' => 'Contract',
        ]);

        \App\Models\Job::create([
            'title' => 'UI/UX Designer',
            'company' => 'Creative Studio',
            'location' => 'Bandung',
            'type' => 'Full-time',
        ]);
    }
}
