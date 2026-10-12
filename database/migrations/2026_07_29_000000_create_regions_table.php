<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('min_order_price', 10, 2)->nullable();
            $table->integer('min_order_products')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Prepopulate with Alexandria regions
        $regions = [
            'أبو قير', 'طوسون', 'المعمورة البلد', 'المعمورة الشاطئ', 'المنتزه', 
            'المندرة بحري', 'المندرة قبلي', 'العصافرة بحري', 'العصافرة قبلي', 'ميامي', 
            'سيدي بشر بحري', 'سيدي بشر قبلي', 'دربالة', 'السيوف', 'الفلكي', 'خورشيد', 
            'سان ستيفانو', 'جليم', 'زيزينيا', 'سابا باشا', 'جناكليس', 'بولكلي', 'شدس', 
            'صفر', 'باكوس', 'غبريال', 'كفر عبده', 'رشدي', 'مصطفى كامل', 'سيدي جابر', 
            'سموحة', 'الإبراهيمية', 'كامب شيزار', 'الشاطبي', 'الأزاريطة', 'محطة الرمل', 
            'العطارين', 'كوم الدكة', 'محطة مصر', 'محرم بك', 'أمبروزو', 'الحضرة بحري', 
            'الحضرة قبلي', 'نادي الصيد', 'حجر النواتية', 'بحري', 'الأنفوشي', 'رأس التين', 
            'المنشية', 'السيالة', 'الحجاري', 'مينا البصل', 'القباري', 'الورديان', 
            'كرموز', 'غيط العنب', 'بشائر الخير', 'المكس', 'الدخيلة', 'البيطاش', 
            'الهانوفيل', '6 أكتوبر (النخيل)', 'أبو يوسف', 'الكيلو 21', 'الصفا', 
            'أم زغيو', 'أبو تلات', 'مرغم', 'عبد القادر', 'زاوية عبد القادر', 
            'العامرية البلد', 'النهضة', 'كينج مريوط', 'الهوارية', 'برج العرب القديمة', 
            'برج العرب الجديدة', 'بهيج', 'أبو صير'
        ];

        $now = now();
        $insertData = array_map(function($name) use ($now) {
            return [
                'name' => $name,
                'min_order_price' => 0.00,
                'min_order_products' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }, $regions);

        DB::table('regions')->insert($insertData);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
