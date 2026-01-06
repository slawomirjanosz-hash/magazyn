<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Part;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tworzenie głównego konta Admin proximalumine (jeśli nie istnieje)
        if (!User::where('email', 'proximalumine@gmail.com')->exists()) {
            User::create([
                'name' => 'ProximaLumine',
                'first_name' => 'Proxima',
                'last_name' => 'Lumine',
                'short_name' => 'ProLum',
                'email' => 'proximalumine@gmail.com',
                'password' => \Illuminate\Support\Facades\Hash::make('Lumine1!'),
                'is_admin' => true,
                'can_view_catalog' => true,
                'can_add' => true,
                'can_remove' => true,
                'can_orders' => true,
                'can_settings' => true,
                'can_settings_categories' => true,
                'can_settings_suppliers' => true,
                'can_settings_company' => true,
                'can_settings_users' => true,
                'can_settings_export' => true,
                'can_settings_other' => true,
                'can_delete_orders' => true,
                'show_action_column' => true,
            ]);
        }

        // Tworzenie konta admin (login: admin, email: admin@admin.com, hasło: admin)
        if (!User::where('email', 'admin@admin.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => \Illuminate\Support\Facades\Hash::make('admin'),
                'is_admin' => true,
                'can_view_catalog' => true,
                'can_add' => true,
                'can_remove' => true,
                'can_orders' => true,
                'can_settings' => true,
            ]);
        }

        // Test User (jeśli nie istnieje)
        if (!User::where('email', 'test@example.com')->exists()) {
            User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('test'),
                'is_admin' => false,
                'can_view_catalog' => true,
                'can_add' => false,
                'can_remove' => false,
                'can_orders' => false,
                'can_settings' => false,
            ]);
        }

        // Kategorie
        $kategorieNames = [
            'Elektronika',
            'Mechanika',
            'Oprogramowanie',
            'Akcesoria',
            'Materiały zużywalne'
        ];

        foreach ($kategorieNames as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        // Przykładowe produkty
        $parts = [
            ['name' => 'Arduino Uno', 'description' => 'Mikrokontroler Arduino', 'category_id' => 1, 'quantity' => 15],
            ['name' => 'Raspberry Pi 4', 'description' => 'Komputer jednopłytkowy', 'category_id' => 1, 'quantity' => 8],
            ['name' => 'Czujnik temperatury DS18B20', 'description' => 'Cyfrowy czujnik temperatury', 'category_id' => 1, 'quantity' => 42],
            ['name' => 'Dioda LED RGB', 'description' => '5mm RGB LED', 'category_id' => 1, 'quantity' => 100],
            ['name' => 'Rezystor 10k', 'description' => 'Rezystor 1/4W', 'category_id' => 1, 'quantity' => 500],
            ['name' => 'Silnik krokowy NEMA 17', 'description' => 'Silnik krokowy', 'category_id' => 2, 'quantity' => 5],
            ['name' => 'Łożysko kulkowe 608', 'description' => 'Łożysko 8x22x7mm', 'category_id' => 2, 'quantity' => 20],
            ['name' => 'Śruba M4x20', 'description' => 'Śruba stalowa', 'category_id' => 2, 'quantity' => 200],
            ['name' => 'Python', 'description' => 'Licencja Python', 'category_id' => 3, 'quantity' => 1],
            ['name' => 'USB Kabel', 'description' => 'Kabel USB A-B', 'category_id' => 4, 'quantity' => 25],
            ['name' => 'Pasta termalna', 'description' => 'Pasta termalna do procesorów', 'category_id' => 5, 'quantity' => 10],
        ];

        foreach ($parts as $part) {
            Part::firstOrCreate(['name' => $part['name']], $part);
        }
        
        // Ustawienia firmy i zamówień
        $this->call([
            CompanySettingsSeeder::class,
        ]);
        
        // Upewnij się że order_settings ma domyślne wartości
        if (!\DB::table('order_settings')->exists()) {
            \DB::table('order_settings')->insert([
                'element1_type' => 'text',
                'element1_value' => 'ZAM',
                'separator1' => '_',
                'element2_type' => 'date',
                'element2_value' => 'yyyymmdd',
                'separator2' => '_',
                'element3_type' => 'number',
                'start_number' => 1,
                'separator3' => '_',
                'element4_type' => 'supplier_short_name',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

