<!DOCTYPE html>
<html>
<head>
    <title>Railway Diagnostics</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .ok { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
        h2 { border-bottom: 2px solid #0f0; }
    </style>
</head>
<body>
    <h1>🔍 Railway Database Diagnostics</h1>
    
    <h2>Database Connection</h2>
    @php
        try {
            DB::connection()->getPdo();
            echo '<div class="ok">✓ Database connected successfully</div>';
            echo '<div>Driver: ' . DB::connection()->getDriverName() . '</div>';
            echo '<div>Database: ' . DB::connection()->getDatabaseName() . '</div>';
        } catch (\Exception $e) {
            echo '<div class="error">✗ Database connection failed: ' . $e->getMessage() . '</div>';
        }
    @endphp
    
    <h2>Tables Check</h2>
    @php
        $requiredTables = ['offers', 'crm_companies', 'crm_deals', 'crm_stages', 'users'];
        $existingTables = DB::select('SHOW TABLES');
        $dbName = DB::connection()->getDatabaseName();
        $tableList = array_map(function($table) use ($dbName) {
            $key = 'Tables_in_' . $dbName;
            return $table->$key;
        }, $existingTables);
        
        echo '<pre>Existing tables: ' . implode(', ', $tableList) . '</pre>';
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $tableList)) {
                echo '<div class="ok">✓ Table exists: ' . $table . '</div>';
            } else {
                echo '<div class="error">✗ Table missing: ' . $table . '</div>';
            }
        }
    @endphp
    
    <h2>Offers Table Structure</h2>
    @php
        try {
            $columns = DB::select('DESCRIBE offers');
            echo '<pre>';
            foreach ($columns as $column) {
                echo $column->Field . ' (' . $column->Type . ') ' . ($column->Null == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
            }
            echo '</pre>';
            
            $requiredColumns = ['crm_deal_id', 'customer_name', 'customer_nip', 'custom_sections'];
            foreach ($requiredColumns as $col) {
                $found = false;
                foreach ($columns as $column) {
                    if ($column->Field === $col) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    echo '<div class="ok">✓ Column exists: ' . $col . '</div>';
                } else {
                    echo '<div class="error">✗ Column missing: ' . $col . '</div>';
                }
            }
        } catch (\Exception $e) {
            echo '<div class="error">Error: ' . $e->getMessage() . '</div>';
        }
    @endphp
    
    <h2>Models Check</h2>
    @php
        $models = [
            'App\Models\Offer',
            'App\Models\CrmDeal', 
            'App\Models\CrmCompany',
            'App\Models\User'
        ];
        
        foreach ($models as $model) {
            if (class_exists($model)) {
                echo '<div class="ok">✓ Model exists: ' . $model . '</div>';
                try {
                    $count = $model::count();
                    echo '<div>  Records: ' . $count . '</div>';
                } catch (\Exception $e) {
                    echo '<div class="error">  Error counting: ' . $e->getMessage() . '</div>';
                }
            } else {
                echo '<div class="error">✗ Model missing: ' . $model . '</div>';
            }
        }
    @endphp
    
    <h2>Migrations Status</h2>
    @php
        try {
            $migrations = DB::table('migrations')->orderBy('batch', 'desc')->get();
            echo '<div class="ok">✓ Total migrations run: ' . $migrations->count() . '</div>';
            echo '<div class="warning">Last 5 migrations:</div>';
            echo '<pre>';
            foreach ($migrations->take(5) as $migration) {
                echo $migration->migration . ' (batch ' . $migration->batch . ")\n";
            }
            echo '</pre>';
        } catch (\Exception $e) {
            echo '<div class="error">Error: ' . $e->getMessage() . '</div>';
        }
    @endphp
    
    <h2>Environment Info</h2>
    <pre>PHP Version: {{ PHP_VERSION }}
Laravel Version: {{ app()->version() }}
Environment: {{ app()->environment() }}
Debug Mode: {{ config('app.debug') ? 'ON' : 'OFF' }}</pre>
    
    <h2>Test Query</h2>
    @php
        try {
            $companies = DB::table('crm_companies')->count();
            echo '<div class="ok">✓ CRM Companies count: ' . $companies . '</div>';
        } catch (\Exception $e) {
            echo '<div class="error">✗ Error querying crm_companies: ' . $e->getMessage() . '</div>';
        }
        
        try {
            $offers = DB::table('offers')->count();
            echo '<div class="ok">✓ Offers count: ' . $offers . '</div>';
        } catch (\Exception $e) {
            echo '<div class="error">✗ Error querying offers: ' . $e->getMessage() . '</div>';
        }
    @endphp
    
    <h2>Action Required</h2>
    @php
        $errors = [];
        
        // Check if crm tables exist
        if (!in_array('crm_companies', $tableList)) {
            $errors[] = 'Run migrations: php artisan migrate --force';
        }
        
        // Check if offer columns exist
        $offerColumns = DB::select('DESCRIBE offers');
        $hasCustomSections = false;
        foreach ($offerColumns as $col) {
            if ($col->Field === 'custom_sections') {
                $hasCustomSections = true;
                break;
            }
        }
        
        if (!$hasCustomSections) {
            $errors[] = 'Run specific migration: php artisan migrate --path=database/migrations/2026_01_16_131228_add_custom_sections_to_offers_table.php --force';
        }
        
        if (empty($errors)) {
            echo '<div class="ok">✓ Everything looks good!</div>';
        } else {
            echo '<div class="error">Issues found:</div>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '</ul>';
        }
    @endphp
    
    <h2>Test /wyceny/nowa Route</h2>
    @php
        echo '<div>Testing route loading...</div>';
        try {
            // Test if view file exists
            if (view()->exists('offers-new')) {
                echo '<div class="ok">✓ View file exists: offers-new.blade.php</div>';
            } else {
                echo '<div class="error">✗ View file missing: offers-new.blade.php</div>';
            }
            
            // Test route registration
            $routes = Route::getRoutes();
            $found = false;
            foreach ($routes as $route) {
                if ($route->getName() === 'offers.new') {
                    $found = true;
                    echo '<div class="ok">✓ Route registered: offers.new</div>';
                    echo '<div>URI: ' . $route->uri() . '</div>';
                    echo '<div>Method: ' . implode('|', $route->methods()) . '</div>';
                    break;
                }
            }
            if (!$found) {
                echo '<div class="error">✗ Route not registered: offers.new</div>';
            }
            
            // Try to actually render the view
            echo '<div>Attempting to render view...</div>';
            try {
                $dealId = null;
                $deal = null;
                $companies = [];
                
                if (class_exists('\App\Models\CrmCompany')) {
                    $companies = \App\Models\CrmCompany::with('supplier')->orderBy('name')->get();
                }
                
                $viewContent = view('offers-new', ['deal' => $deal, 'companies' => $companies])->render();
                echo '<div class="ok">✓ View renders successfully!</div>';
                echo '<div>View size: ' . strlen($viewContent) . ' bytes</div>';
            } catch (\Exception $e) {
                echo '<div class="error">✗ View render failed!</div>';
                echo '<div class="error">Error: ' . $e->getMessage() . '</div>';
                echo '<div class="error">File: ' . $e->getFile() . '</div>';
                echo '<div class="error">Line: ' . $e->getLine() . '</div>';
                echo '<pre class="error">' . $e->getTraceAsString() . '</pre>';
            }
            
        } catch (\Exception $e) {
            echo '<div class="error">✗ Route test failed: ' . $e->getMessage() . '</div>';
        }
    @endphp
    
    <hr>
    <p><a href="/wyceny/nowa" style="color: #0f0;">Try /wyceny/nowa again</a></p>
</body>
</html>
