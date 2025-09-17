<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
<<<<<<< HEAD
        $this->call([
            RunInProductionSeeder::class,
            SchoolSeeder::class,
            ClassGroupSeeder::class,
            MyClassSeeder::class,
            SectionSeeder::class,
            UserSeeder::class,
            StudentSeeder::class,
            SubjectSeeder::class,
            AcademicYearSeeder::class,
            SemesterSeeder::class,
            PromotionSeeder::class,
            SyllabusSeeder::class,
            TimetableSeeder::class,
            ExamSeeder::class,
            GradeSystemSeeder::class,
            ExamSlotSeeder::class,
            ExamRecordSeeder::class,
            NoticeSeeder::class,
            FeeCategorySeeder::class,
            FeeSeeder::class,
            FeeInvoiceSeeder::class,
            FeeInvoiceRecordSeeder::class,
        ]);
=======
        // \App\Models\User::factory(10)->create();
>>>>>>> 80e3dc5 (First commit)
    }
}
