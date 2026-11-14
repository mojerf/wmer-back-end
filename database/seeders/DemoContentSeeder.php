<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\Support\PlaceholderImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Realistic Persian demo content (works, posts, products, comments, orders, contact messages) so the
 * whole site can be explored locally. Never runs in production (see DatabaseSeeder).
 */
class DemoContentSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string}> */
    private const COVERS = [
        ['#4c1d95', '#22d3ee'],
        ['#0e7490', '#34d399'],
        ['#9d174d', '#fbbf24'],
        ['#1e3a8a', '#a78bfa'],
        ['#065f46', '#22d3ee'],
        ['#7c2d12', '#f472b6'],
        ['#312e81', '#fb7185'],
        ['#134e4a', '#facc15'],
    ];

    public function run(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $customers = $this->customers();

        // Extra filler first, so lists paginate; the curated items below get the highest ids (shown first).
        $this->extraContent($admin);

        $works = $this->works($admin);
        $posts = $this->posts($admin);
        $products = $this->products($admin);

        $this->comments($posts->all(), $customers);
        $this->comments($works->all(), $customers, max: 3);
        $this->comments($products->all(), $customers, max: 3);
        $this->orders($customers, $products);
        $this->contacts();
    }

    /** @return Collection<int, User> */
    private function customers(): Collection
    {
        $people = [
            ['علی', 'رضایی'], ['مریم', 'احمدی'], ['سارا', 'کریمی'], ['امیرحسین', 'موسوی'],
            ['نگار', 'صادقی'], ['کیان', 'حسینی'], ['هانیه', 'جعفری'], ['پوریا', 'نظری'],
        ];

        $customers = collect([
            // A well-known demo customer to sign in with: user@example.test / password
            User::factory()->create([
                'first_name' => 'کاربر',
                'last_name' => 'نمونه',
                'email' => 'user@example.test',
                'phone_number' => '09120000001',
            ]),
        ]);

        foreach ($people as $i => [$first, $last]) {
            $customers->push(User::factory()->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => 'customer'.($i + 1).'@example.test',
                'phone_number' => '0912'.str_pad((string) (1000 + $i), 7, '0', STR_PAD_LEFT),
            ]));
        }

        return $customers;
    }

    /** @return Collection<int, Work> */
    private function works(User $admin): Collection
    {
        $items = [
            ['sales-dashboard', 'پنل مدیریت فروش', 'Angular,Laravel,Sanctum', 'داشبورد زنده با نمودارهای لحظه‌ای، نقش‌های دسترسی و گزارش‌گیری برای تیم فروش.'],
            ['booking-app', 'اپ رزرو آنلاین', 'PWA,Tailwind,Laravel', 'رزرو نوبت با پشتیبانی آفلاین، اعلان‌ها و پرداخت آنلاین.'],
            ['digital-shop', 'فروشگاه محصولات دیجیتال', 'Laravel,MySQL,Angular', 'فروش قالب و دوره با تحویل خودکار لینک دانلود پس از تکمیل سفارش.'],
            ['wmer-site', 'سایت شخصی wmer', 'Angular,SSR,Tailwind', 'همین سایت: رندر سمت سرور، سئوی کامل و معماری feature-based.'],
            ['cms-platform', 'سامانه‌ی مدیریت محتوا', 'Laravel,Vue,MySQL', 'سیستم انتشار محتوا برای یک رسانه‌ی خبری با ویرایشگر و زمان‌بندی انتشار.'],
            ['analytics-board', 'داشبورد تحلیلی', 'TypeScript,Angular,Charts', 'نمایش شاخص‌های کلیدی کسب‌وکار با نمودارهای تعاملی و خروجی گزارش.'],
        ];

        return collect($items)->map(function (array $item, int $i) use ($admin): Work {
            [$slug, $title, $tags, $overview] = $item;

            $work = Work::create([
                'user_id' => $admin->id,
                ...PlaceholderImage::pair("demo/works/{$slug}.png", self::COVERS[$i % count(self::COVERS)]),
                'full_image' => PlaceholderImage::create("demo/works/{$slug}-full.png", self::COVERS[($i + 3) % count(self::COVERS)], 1600, 900),
                'title' => $title,
                'slug' => $slug,
                'timeline' => 'اردیبهشت ۱۴۰۴ تا مرداد ۱۴۰۴',
                'publish_date' => 'مرداد ۱۴۰۴',
                'role' => 'طراح و توسعه‌دهنده‌ی Full-stack',
                'tags' => $tags,
                'project_link' => 'https://example.com/projects/'.$slug,
                'overview' => $overview,
                'learn' => 'در این پروژه یاد گرفتم چطور مرز بین لایه‌ها را تمیز نگه دارم و از همان ابتدا برای تست و استقرار برنامه‌ریزی کنم.',
                'description' => "این پروژه از صفر طراحی و پیاده‌سازی شد.\n\nتمرکز اصلی روی سرعت بارگذاری، دسترس‌پذیری و تجربه‌ی کاربری روان بود. بک‌اند با Laravel و فرانت‌اند با Angular نوشته شد و ارتباطشان از طریق یک API نسخه‌دار انجام می‌شود.\n\nنتیجه یک محصول پایدار است که به‌راحتی می‌شود آن را گسترش داد.",
            ]);

            return $this->backdate($work, now()->subDays(120 - $i * 15));
        });
    }

    /** @return Collection<int, Post> */
    private function posts(User $admin): Collection
    {
        $items = [
            ['zoneless-angular', 'چرا Angular را zoneless کردم', 'حذف zone.js کد را ساده‌تر و سریع‌تر می‌کند؛ در این نوشته تجربه‌ی مهاجرت و نکته‌هایش را می‌گویم.'],
            ['ssr-seo', 'SSR و سئو؛ راهنمای عملی', 'رندر سمت سرور باعث می‌شود گوگل محتوای صفحه را کامل ببیند. چند اشتباه رایج را هم مرور می‌کنم.'],
            ['secure-laravel-api', 'ساخت API امن با لاراول و Sanctum', 'احراز هویت کوکی‌محور برای SPA، محدودیت نرخ درخواست و اعتبارسنجی؛ چک‌لیستی برای API بدون سوراخ.'],
            ['tailwind-4-migration', 'مهاجرت به Tailwind 4', 'پیکربندی حالا داخل CSS است. ببینیم چه چیزهایی عوض شد و چه چیزهایی باید دست‌کاری شوند.'],
            ['laravel-13-upgrade', 'ارتقا به Laravel 13 بدون استرس', 'ارتقای نسخه با پشتوانه‌ی تست ساده می‌شود. مراحلی که من طی کردم را بخوانید.'],
            ['testing-with-pest', 'تست‌نویسی با Pest', 'تست‌های خوانا برای API: از احراز هویت تا مجوزها و محدودیت نرخ.'],
            ['rtl-accessibility', 'دسترس‌پذیری در طراحی راست‌به‌چپ', 'جهت متن، آیکن‌ها، فوکوس و خوانندگان صفحه؛ نکته‌هایی که در سایت‌های فارسی نادیده گرفته می‌شود.'],
            ['deploy-checklist', 'چک‌لیست استقرار یک اپ Angular و Laravel', 'از متغیرهای محیطی و هدرهای امنیتی تا کش و پایش؛ قبل از انتشار این‌ها را بررسی کنید.'],
        ];

        return collect($items)->map(function (array $item, int $i) use ($admin): Post {
            [$slug, $title, $lead] = $item;

            $post = Post::create([
                'user_id' => $admin->id,
                ...PlaceholderImage::pair("demo/posts/{$slug}.png", self::COVERS[($i + 1) % count(self::COVERS)]),
                'title' => $title,
                'slug' => $slug,
                'description' => $lead."\n\nدر این مدت چند پروژه را با این رویکرد پیش بردم و نتیجه‌ها قابل‌اتکا بود. مهم‌ترین درس این است که تصمیم‌های کوچک و مداوم برای تمیز نگه داشتن کد، در بلندمدت بیشترین بازده را دارند.\n\nاگر سؤالی دارید، در بخش نظرها بنویسید.",
            ]);

            return $this->backdate($post, now()->subDays(3 + $i * 6));
        });
    }

    /** @return Collection<int, Product> */
    private function products(User $admin): Collection
    {
        $items = [
            ['angular-dashboard-template', 'قالب داشبورد Angular', 650_000, 490_000, 'قالب آماده با Signals و Tailwind'],
            ['laravel-course', 'دوره‌ی لاراول از صفر', 1_200_000, null, 'از نصب تا استقرار روی سرور'],
            ['sanctum-starter', 'استارتر Laravel + Sanctum', 380_000, 290_000, 'پروژه‌ی آماده برای شروع سریع'],
            ['tailwind-ui-kit', 'کیت UI برای Tailwind', 420_000, null, 'کامپوننت‌های راست‌به‌چپ و تیره/روشن'],
            ['seo-guide', 'راهنمای سئوی Angular', 250_000, 199_000, 'کتاب الکترونیکی درباره‌ی SSR و سئو'],
            ['testing-workshop', 'کارگاه تست‌نویسی', 900_000, 750_000, 'تست API با Pest و تست کامپوننت با Vitest'],
        ];

        return collect($items)->map(function (array $item, int $i) use ($admin): Product {
            [$slug, $title, $price, $discount, $expert] = $item;

            $product = Product::create([
                'user_id' => $admin->id,
                ...PlaceholderImage::pair("demo/products/{$slug}.png", self::COVERS[($i + 2) % count(self::COVERS)]),
                'title' => $title,
                'slug' => $slug,
                'price' => $price,
                'price_with_discount' => $discount,
                'expert' => $expert,
                'description' => "{$expert}.\n\nاین محصول بر اساس تجربه‌ی واقعی پروژه‌ها ساخته شده و به‌روزرسانی‌های بعدی هم رایگان است. بعد از تکمیل سفارش، لینک دانلود برای شما فعال می‌شود.",
                'download_link' => 'https://files.example.com/downloads/'.$slug.'.zip',
            ]);

            return $this->backdate($product, now()->subDays(10 + $i * 9));
        });
    }

    /**
     * @param  iterable<int, Work|Post|Product>  $items
     * @param  Collection<int, User>  $customers
     */
    private function comments(iterable $items, Collection $customers, int $max = 4): void
    {
        $bodies = [
            'مطلب خیلی خوبی بود، ممنون از وقتی که گذاشتید.',
            'من هم همین مشکل را داشتم؛ با این روش حل شد.',
            'ممکنه درباره‌ی جزئیات پیاده‌سازی بیشتر توضیح بدید؟',
            'عالی بود. منتظر نوشته‌های بعدی هستم.',
            'نکته‌ی آخر واقعاً به کارم اومد.',
            'یه سؤال: این روش برای پروژه‌های بزرگ هم جواب می‌ده؟',
        ];
        $replies = [
            'خوشحالم که به کارتون اومد!',
            'بله، توی چند پروژه‌ی بزرگ هم امتحانش کردم و مشکلی نداشت.',
            'حتماً در یک نوشته‌ی جدا کامل‌تر توضیح می‌دم.',
        ];

        foreach ($items as $n => $item) {
            $count = ($n * 3 + 1) % ($max + 1);

            for ($i = 0; $i < $count; $i++) {
                $author = $customers[($n + $i) % $customers->count()];
                $comment = $item->comments()->create([
                    'user_id' => $author->id,
                    'body' => $bodies[($n + $i) % count($bodies)],
                ]);
                $this->backdate($comment, now()->subDays(max(1, 2 + $n - $i))->subMinutes($i * 37));

                if ($i === 0 && $n % 2 === 0) {
                    $reply = $item->comments()->create([
                        'user_id' => $item->user_id,
                        'parent_id' => $comment->getKey(),
                        'body' => $replies[$n % count($replies)],
                    ]);
                    $this->backdate($reply, now()->subDays(1)->subMinutes(10));
                }
            }
        }
    }

    /**
     * Orders in every status so the admin views and the order lifecycle can be tried.
     *
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, Product>  $products
     */
    private function orders(Collection $customers, Collection $products): void
    {
        $statuses = [
            OrderStatus::Completed, OrderStatus::Completed, OrderStatus::Processing, OrderStatus::Pending,
            OrderStatus::Cancelled, OrderStatus::Completed, OrderStatus::Pending, OrderStatus::Processing,
        ];

        foreach ($statuses as $i => $status) {
            $buyer = $customers[$i % $customers->count()];
            $chosen = $products->slice($i % 4, 1 + $i % 3);

            $prices = $chosen->mapWithKeys(fn (Product $p): array => [$p->id => $p->price_with_discount ?? $p->price]);

            $order = Order::create([
                'user_id' => $buyer->id,
                'status' => $status,
                'total_price' => $prices->sum(),
            ]);
            $order->products()->attach($prices->map(fn (int $price): array => ['op_price' => $price])->all());
            $this->backdate($order, now()->subDays(2 + $i * 4));
        }
    }

    private function contacts(): void
    {
        $messages = [
            ['sara@example.test', 'همکاری روی پروژه‌ی فروشگاهی', 'سلام، یک فروشگاه اینترنتی می‌خواهیم و دنبال توسعه‌دهنده‌ی Angular و Laravel هستیم. امکان گفت‌وگو هست؟'],
            ['ali@example.test', 'درخواست مشاوره‌ی سئو', 'سایت ما با Angular ساخته شده و گوگل صفحه‌هایش را درست نمی‌بیند. می‌شود درباره‌ی SSR مشاوره بگیریم؟'],
            ['maryam@example.test', 'سؤال درباره‌ی دوره‌ی لاراول', 'آیا دوره پشتیبانی هم دارد؟ و مناسب افراد مبتدی است؟'],
            ['reza@example.test', 'گزارش مشکل', 'در صفحه‌ی وبلاگ هنگام جست‌وجوی عبارت فارسی نتیجه‌ای نمایش داده نشد.'],
            ['negar@example.test', 'پیشنهاد نوشتن مطلب', 'خیلی دوست دارم درباره‌ی تست‌نویسی در Angular هم بنویسید.'],
            ['kian@example.test', 'استخدام', 'تیم ما به یک توسعه‌دهنده‌ی Full-stack نیاز دارد. رزومه‌تان را برایتان ارسال کنم؟'],
        ];

        foreach ($messages as $i => [$email, $title, $text]) {
            $contact = Contact::create([
                'email' => $email,
                'title' => $title,
                'text' => $text,
                'phone_number' => $i % 2 === 0 ? '0912000'.str_pad((string) (100 + $i), 4, '0', STR_PAD_LEFT) : null,
            ]);
            $this->backdate($contact, now()->subDays($i * 2));
        }
    }

    /**
     * Spreads demo records over time so lists, sorting and relative dates look realistic.
     *
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function backdate(Model $model, Carbon $at): Model
    {
        $model->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();

        return $model;
    }

    /** Filler works, posts and products (Persian placeholder text) so list pages have several pages. */
    private function extraContent(User $admin): void
    {
        $faker = fake('fa_IR');
        $title = fn (int $words): string => rtrim($faker->sentence($words), '.');

        for ($n = 1; $n <= 18; $n++) {
            $work = Work::create([
                'user_id' => $admin->id,
                ...PlaceholderImage::pair("demo/works/extra-{$n}.png", self::COVERS[$n % count(self::COVERS)]),
                'title' => $title(4),
                'slug' => "demo-work-{$n}",
                'timeline' => 'فروردین ۱۴۰۳ تا شهریور ۱۴۰۳',
                'publish_date' => 'شهریور ۱۴۰۳',
                'role' => 'توسعه‌دهنده',
                'tags' => collect(['Angular', 'Laravel', 'TypeScript', 'MySQL', 'Tailwind', 'PWA'])->shuffle()->take(3)->implode(','),
                'overview' => $faker->sentence(12),
                'description' => $faker->paragraph(4),
            ]);
            $this->backdate($work, now()->subDays(200 + $n * 7));
        }

        for ($n = 1; $n <= 24; $n++) {
            $post = Post::create([
                'user_id' => $admin->id,
                ...PlaceholderImage::pair("demo/posts/extra-{$n}.png", self::COVERS[$n % count(self::COVERS)]),
                'title' => $title(6),
                'slug' => "demo-post-{$n}",
                'description' => $faker->paragraph(5).'

'.$faker->paragraph(4),
            ]);
            $this->backdate($post, now()->subDays(60 + $n * 4));
        }

        for ($n = 1; $n <= 12; $n++) {
            $price = random_int(10, 150) * 10_000;
            $product = Product::create([
                'user_id' => $admin->id,
                ...PlaceholderImage::pair("demo/products/extra-{$n}.png", self::COVERS[$n % count(self::COVERS)]),
                'title' => $title(3),
                'slug' => "demo-product-{$n}",
                'price' => $price,
                'price_with_discount' => $n % 3 === 0 ? (int) ($price * 0.8) : null,
                'expert' => $faker->sentence(8),
                'description' => $faker->paragraph(3),
                'download_link' => "https://files.example.com/downloads/demo-product-{$n}.zip",
            ]);
            $this->backdate($product, now()->subDays(90 + $n * 5));
        }
    }
}
