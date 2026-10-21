<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Campaigner;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Donation;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private const PRAYERS = [
        'Semoga Allah mudahkan semua urusan adik-adik di sana. Aamiin.',
        'May this small gift become a big blessing for the families. Keep strong!',
        'Ya Allah, berikan keberkahan untuk para guru ngaji yang ikhlas mengajar.',
        'Praying for everyone affected. You are not alone.',
        'Semoga menjadi amal jariyah untuk almarhumah ibu saya.',
        'May the mosque always be full of people who love You, ya Allah.',
        'Semoga cepat pulih dan kembali beraktivitas seperti biasa.',
        'For my late father. May every verse read from these Qur\'ans light his grave.',
        'Little by little, together we can. Semangat untuk tim relawan!',
        'Semoga anak-anak bisa terus sekolah dan meraih cita-cita.',
        'May Allah replace everything you lost with something better.',
        'Barakallahu fiikum to all the volunteers. Thank you for being our hands.',
    ];

    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Infaqin Admin',
            'username' => 'admin',
            'email' => 'admin@infaqin.test',
            'password' => 'Admin#12345',
        ]);
        $admin->forceFill(['is_admin' => true])->save();

        $demo = User::factory()->create([
            'name' => 'Aisyah Rahmawati',
            'username' => 'aisyah',
            'email' => 'user@infaqin.test',
            'password' => 'Donor#12345',
            'date_of_birth' => '1998-04-12',
            'phone' => '+62 812 3456 7890',
        ]);

        $donors = User::factory(30)->create()->each(function (User $user) {
            $user->forceFill(['username' => Str::slug(Str::before($user->email, '@'), '_').'_'.$user->id])->save();
        });

        $campaigners = collect([
            ['name' => 'Qubba Foundation', 'location' => 'Bandung, West Java', 'bio' => 'A community foundation focused on mosques, Qur\'an distribution and the welfare of Qur\'an teachers across Indonesia since 2016.'],
            ['name' => 'Rumah Yatim Nusantara', 'location' => 'Yogyakarta', 'bio' => 'Caring for orphans and dhuafa children through education scholarships, nutrition and mentoring programs.'],
            ['name' => 'Siaga Bencana ID', 'location' => 'Jakarta', 'bio' => 'A volunteer-run rapid response team delivering logistics, clean water and medical aid to disaster areas.'],
            ['name' => 'Klinik Sehat Desa', 'location' => 'Kupang, NTT', 'bio' => 'Mobile clinics and health workers serving remote villages in Eastern Indonesia.'],
            ['name' => 'Syaamil', 'location' => 'Bandung, West Java', 'bio' => 'Programmes run directly by the Syaamil team.', 'is_internal' => true],
        ])->map(fn ($data) => Campaigner::create($data + ['slug' => Str::slug($data['name']), 'is_verified' => true]));

        [$qubba, $yatim, $siaga, $klinik, $syaamil] = $campaigners->all();

        $categories = Category::pluck('id', 'slug');

        $campaigns = collect([
            [$qubba, 'GEMAS — Gerakan Membersihkan Masjid', 'mosque', 'images/campaigns/gemas.jpg', 'Bandung', 150_000_000, 40, true, false,
                'Join the movement to clean, repair and care for neighbourhood mosques that have been left behind.',
                "Hundreds of small mosques across West Java struggle with leaking roofs, broken wudhu taps and carpets that have not been replaced in decades.\n\nGEMAS mobilises local volunteers every weekend to deep-clean and repair these mosques. Your donation buys cleaning supplies, replacement carpets, water pumps and pays local craftsmen for structural repairs.\n\nEvery mosque we finish gets a before-and-after report on the Open Fund Ledger, so you can see exactly what your infaq built."],
            [$syaamil, 'SSQ — Sebar Sejuta Qur\'an', 'quran', 'images/campaigns/ssq.jpg', 'Across Indonesia', 500_000_000, 75, false, true,
                'One million Qur\'ans for pesantren, TPA and remote mosques that still share a single worn-out mushaf.',
                "In many villages, ten children share one mushaf. Pages are missing, bindings are falling apart and new students wait their turn to recite.\n\nSebar Sejuta Qur'an distributes new, high-quality Qur'ans to TPA, pesantren and mosques across the archipelago. Each Rp75.000 sends one Qur'an — including shipping to the most remote islands.\n\nWe document every distribution with photos and recipient lists."],
            [$syaamil, 'SAQTI — Sedekah Qur\'an untuk Indonesia', 'quran', 'images/campaigns/saqti.png', 'Eastern Indonesia', 250_000_000, 55, false, true,
                'Qur\'ans with translation and tajwid guides for new Muslims and learners in Eastern Indonesia.',
                "SAQTI focuses on communities in Maluku, Papua and NTT, where Qur'ans with Indonesian translation and tajwid guidance are especially scarce.\n\nAlongside the Qur'ans, we fund weekly study circles led by local teachers so the gift is not only received, but understood and lived."],
            [$qubba, '1000 Kado untuk Guru Ngaji', 'education', 'images/campaigns/kado-guru-ngaji.png', 'West Java', 200_000_000, 21, true, true,
                'Honour the quiet heroes who teach our children to read the Qur\'an — often without any salary.',
                "Most guru ngaji teach after Maghrib every single day, for free. Many live below the poverty line.\n\nThis campaign sends 1,000 gift packages containing groceries, a new set of clothes, a prayer mat and a cash stipend to guru ngaji across West Java. Saatnya memuliakan guru ngaji — it is time to honour them."],
            [$siaga, 'Emergency Aid Packages for Flood Survivors', 'disaster', 'images/campaigns/volunteer-gift.jpg', 'Demak, Central Java', 300_000_000, 10, true, false,
                'Thousands of families lost their homes to flooding. Food, clean water and hygiene kits are needed now.',
                "Flash floods have displaced more than 8,000 families. Our volunteers are already on the ground distributing family packages: rice, cooking oil, clean water, baby supplies and hygiene kits.\n\nEach Rp250.000 provides one family package for a week. Withdrawals are published on the ledger within 24 hours of each distribution."],
            [$yatim, 'Scholarships for 100 Orphans', 'orphans', 'images/campaigns/handover.jpg', 'Yogyakarta', 180_000_000, 90, false, false,
                'Keep orphaned children in school with tuition, uniforms, books and monthly mentoring.',
                "School fees are often the first thing to go when a family loses its breadwinner. This scholarship covers a full year of tuition, uniforms, books and transport for 100 orphans from primary to high school.\n\nEach scholar is paired with a volunteer mentor who meets them monthly."],
            [$klinik, 'Mobile Clinic for Remote Villages', 'health', 'images/campaigns/team.jpg', 'Kupang, NTT', 350_000_000, 60, false, false,
                'A fully equipped mobile clinic to reach 40 villages that are hours away from the nearest health centre.',
                "For many families in rural NTT, the nearest puskesmas is a four-hour walk. Mothers give birth without a midwife and simple infections become life-threatening.\n\nWe are raising funds for a mobile clinic vehicle, medicines and two years of operating costs including a doctor, a midwife and a nurse."],
            [$yatim, 'Training Programme for Young Teachers', 'education', 'images/campaigns/volunteer.jpg', 'Central Java', 90_000_000, 35, false, false,
                'Equip 60 young teachers in rural schools with modern teaching skills and learning materials.',
                'Great teachers change lives. This programme funds a six-month training course, teaching kits and monthly coaching for young teachers in rural Central Java.'],
            [$klinik, 'Clean Water Wells for Dry Villages', 'health', null, 'Sumba, NTT', 120_000_000, 50, true, false,
                'In the dry season, children walk 5 km for water. Let\'s build 12 deep wells close to their homes.',
                "Every dry season, wells in Sumba run dry and children skip school to fetch water.\n\nWe will drill 12 deep wells with solar-powered pumps, each serving around 150 families, and train local committees to maintain them."],
        ])->map(function ($c) use ($categories) {
            [$campaigner, $title, $category, $image, $location, $target, $days, $urgent, $featured, $summary, $description] = $c;

            return Campaign::create([
                'campaigner_id' => $campaigner->id,
                'category_id' => $categories[$category],
                'title' => $title,
                'slug' => Str::slug($title),
                'image' => $image,
                'summary' => $summary,
                'description' => $description,
                'location' => $location,
                'target_amount' => $target,
                'deadline' => now()->addDays($days)->toDateString(),
                'is_urgent' => $urgent,
                'is_featured' => $featured,
                'status' => Campaign::STATUS_ACTIVE,
            ]);
        });

        $methods = array_keys(Donation::PAYMENT_METHODS);
        $amounts = [25_000, 50_000, 50_000, 100_000, 100_000, 150_000, 250_000, 500_000, 1_000_000, 2_500_000];
        $prayers = collect(self::PRAYERS)->shuffle();

        $makeDonation = function (User $user, Campaign $campaign, ?\DateTimeInterface $paidAt = null, ?int $amount = null) use ($methods, $amounts) {
            $donation = new Donation([
                'campaign_id' => $campaign->id,
                'amount' => $amount ?? Arr::random($amounts),
                'donor_name' => $user->name,
                'email' => $user->email,
                'payment_method' => Arr::random($methods),
                'is_anonymous' => random_int(1, 5) === 1,
            ]);
            $donation->user()->associate($user);
            $donation->status = Donation::STATUS_PAID;
            $donation->paid_at = $paidAt ?? now()->subDays(random_int(0, 60))->subMinutes(random_int(0, 1440));
            $donation->created_at = $donation->paid_at;
            $donation->save();

            return $donation;
        };

        foreach ($campaigns as $campaign) {
            foreach ($donors->random(random_int(8, 22)) as $donor) {
                $donation = $makeDonation($donor, $campaign);

                if (random_int(1, 3) === 1) {
                    $this->addComment($donation, $prayers->shift() ?? Arr::random(self::PRAYERS), $donors);
                }
            }
        }

        // Demo donor: a 4-week kindness streak plus a pending and a failed donation.
        foreach ([0, 7, 14, 21] as $i => $daysAgo) {
            $donation = $makeDonation($demo, $campaigns[$i], now()->subDays($daysAgo)->setTime(9, 30), [100_000, 50_000, 250_000, 75_000][$i]);
            if ($i === 1) {
                $this->addComment($donation, 'Semoga Al-Qur\'an ini menjadi cahaya bagi setiap rumah yang menerimanya. Aamiin.', $donors);
            }
        }

        // Seeded pending donations have no Snap token (no Midtrans session); the payment page offers only cancel.
        $pending = new Donation(['campaign_id' => $campaigns[4]->id, 'amount' => 150_000, 'donor_name' => $demo->name, 'email' => $demo->email]);
        $pending->user()->associate($demo);
        $pending->status = Donation::STATUS_PENDING;
        $pending->save();

        $failed = new Donation(['campaign_id' => $campaigns[6]->id, 'amount' => 30_000, 'donor_name' => $demo->name, 'email' => $demo->email, 'payment_method' => 'ovo']);
        $failed->user()->associate($demo);
        $failed->status = Donation::STATUS_FAILED;
        $failed->created_at = now()->subDays(30);
        $failed->save();

        $expired = new Donation(['campaign_id' => $campaigns[5]->id, 'amount' => 20_000, 'donor_name' => $demo->name, 'email' => $demo->email]);
        $expired->user()->associate($demo);
        $expired->status = Donation::STATUS_EXPIRED;
        $expired->created_at = now()->subDays(45);
        $expired->save();

        // Recalculate campaign totals from paid donations and record public withdrawals.
        foreach ($campaigns as $campaign) {
            $paid = $campaign->donations()->where('status', Donation::STATUS_PAID);
            $campaign->forceFill([
                'collected_amount' => (int) (clone $paid)->sum('amount'),
                'donor_count' => (clone $paid)->distinct()->count('email'),
            ])->save();

            $remaining = $campaign->collected_amount;
            $purposes = [
                'Purchase of supplies for the first distribution batch',
                'Logistics and transport to the field location',
                'Payment to local partners and craftsmen',
                'Second distribution batch, documented with photos',
            ];

            foreach (array_slice($purposes, 0, random_int(1, 3)) as $n => $purpose) {
                $amount = (int) (floor($remaining * 0.3 / 1000) * 1000);
                if ($amount < 100_000) {
                    break;
                }
                $campaign->withdrawals()->create([
                    'amount' => $amount,
                    'description' => $purpose,
                    'withdrawn_at' => now()->subDays(30 - $n * 9),
                ]);
                $remaining -= $amount;
            }
        }

        $this->seedNews($admin);
    }

    private function addComment(Donation $donation, string $body, $donors): void
    {
        $comment = new Comment(['body' => $body]);
        $comment->campaign()->associate($donation->campaign_id);
        $comment->user()->associate($donation->user_id);
        $comment->donation()->associate($donation);
        $comment->created_at = $donation->paid_at;
        $comment->save();

        $amenUsers = $donors->random(random_int(0, 12));
        foreach ($amenUsers as $user) {
            DB::table('comment_amens')->insert(['comment_id' => $comment->id, 'user_id' => $user->id, 'created_at' => now()]);
        }
        $comment->forceFill(['amens_count' => $amenUsers->count()])->save();
    }

    private function seedNews(User $admin): void
    {
        $articles = [
            ['Floods displace thousands across Central Java', 'Disaster', 'images/campaigns/volunteer-gift.jpg',
                'Heavy rainfall has pushed rivers past their banks, forcing families into temporary shelters as relief teams race to deliver aid.'],
            ['Global hunger report: one in eleven people still face food insecurity', 'World', null,
                'A new international report shows progress has stalled in several regions, with conflict and climate shocks driving the numbers up.'],
            ['Volunteers deliver 10,000 Qur\'ans to remote islands', 'Infaqin', 'images/campaigns/ssq.jpg',
                'The Sebar Sejuta Qur\'an programme reached its first major milestone this month, with shipments landing in Maluku and Papua.'],
            ['Why clean water is still the fastest way to keep children in school', 'Health', null,
                'Researchers find that a well within walking distance can raise school attendance for girls by more than a third.'],
            ['Teachers in rural areas are the backbone of the nation', 'Education', 'images/campaigns/volunteer.jpg',
                'Thousands of honorary teachers earn less than the minimum wage. Communities are stepping in to support them.'],
            ['Earthquake relief: what aid workers need most in the first 72 hours', 'Humanity', 'images/campaigns/team.jpg',
                'From clean water to trauma counselling, responders explain how donors can make the biggest difference after a disaster.'],
        ];

        foreach ($articles as $i => [$title, $category, $image, $excerpt]) {
            $article = new News([
                'title' => $title,
                'slug' => Str::slug($title),
                'category' => $category,
                'image' => $image,
                'excerpt' => $excerpt,
                'body' => $excerpt."\n\n".implode("\n\n", [
                    'Field teams report that the most urgent needs are clean water, food, shelter and basic medicine. Local volunteers have been working around the clock to reach families in the hardest-hit areas, many of which remain cut off by damaged roads.',
                    'Community organisations say that transparency and speed are what make the difference. Funds that arrive in the first days allow teams to buy supplies locally, which is faster and supports the local economy.',
                    'Infaqin will continue to follow the situation and publish updates. Every withdrawal made by our partner campaigns is published on the Open Fund Ledger so donors can follow their contribution from start to finish.',
                ]),
                'published_at' => now()->subDays($i * 2)->subHours($i),
            ]);
            $article->author()->associate($admin);
            $article->save();
        }
    }
}
