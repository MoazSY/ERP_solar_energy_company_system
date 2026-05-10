<?php

namespace Database\Seeders;

use App\Models\Electrical_device;
use Illuminate\Database\Seeder;

class ElectricalDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $devices = [
            'ثلاجة',
            'مكيف هواء',
            'مضخة ماء',
            'غسالة',
            'نشافة',
            'تلفاز',
            'إنارة',
            'حاسوب',
            'فرن كهربائي',
            'سخان ماء',
            'مروحة',
            'خلاط',
            'غلاية كهربائية',
            'مكواة',
            'شفاط',
        ];

        foreach ($devices as $deviceName) {
            Electrical_device::updateOrCreate(
                ['name' => $deviceName],
                ['name' => $deviceName]
            );
        }
    }
}
