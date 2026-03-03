<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = new \App\Http\Requests\User\IndexUsersRequest();
$rules = $request->rules();

$validator = \Illuminate\Support\Facades\Validator::make([
    'is_active' => 'true',
    'cantidad' => '1',
], $rules);

if ($validator->fails()) {
    echo "Fails: \n";
    print_r($validator->errors()->toArray());
} else {
    echo "Passes\n";
}
