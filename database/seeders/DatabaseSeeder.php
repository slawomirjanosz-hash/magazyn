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
        
        // Upewnij się że qr_settings ma domyślne wartości
        if (!\DB::table('qr_settings')->exists()) {
            \DB::table('qr_settings')->insert([
                'element1_type' => 'product_name',
                'element1_value' => '',
                'separator1' => '_',
                'element2_type' => 'location',
                'element2_value' => '',
                'separator2' => '_',
                'element3_type' => 'date',
                'element3_value' => '',
                'separator3' => '_',
                'element4_type' => 'number',
                'start_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Dodaj domyślne kategorie
        $defaultCategories = ['Automatyka', 'Elektryka', 'Mechanika'];
        foreach ($defaultCategories as $categoryName) {
            if (!Category::where('name', $categoryName)->exists()) {
                Category::create([
                    'name' => $categoryName,
                ]);
            }
        }
    }
}

