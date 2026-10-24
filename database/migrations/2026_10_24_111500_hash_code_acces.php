<?php

use App\Models\CodeAcces;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        CodeAcces::query()->each(function (CodeAcces $code) {
            if (Hash::isHashed($code->code)) {
                return;
            }

            $code->code = Hash::make($code->code);
            $code->save();
        });
    }

    public function down(): void
    {
        // Les codes d'origine ne peuvent pas être restitués.
    }
};
