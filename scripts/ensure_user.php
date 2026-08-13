<?php
require __DIR__.'/../bootstrap/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

$dept = Department::first();
if (!$dept) {
    $dept = Department::create(['name' => 'General', 'is_active' => true]);
}

User::updateOrCreate(
    ['email' => 'bastoxd9@gmail.com'],
    [
        'name' => 'Maximiliano',
        'password' => Hash::make('secret123'),
        'department_id' => $dept->id,
        'role' => 'admin',
        'is_active' => true,
    ]
);

echo "User ensured.\n";
?>
