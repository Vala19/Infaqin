@php
    $company = config('infaqin.company');
    $updated = \Illuminate\Support\Carbon::parse(config('infaqin.terms_updated_at'));
    $vars = [
        'legal_name' => $company['legal_name'],
        'support_email' => $company['support_email'],
        'email' => $company['email'],
        'address' => $company['address'],
        'hours' => \App\Models\Donation::PAYMENT_WINDOW_HOURS,
        'amount' => rupiah(\App\Models\Donation::MIN_AMOUNT),
    ];

    // Each section: [id, title, paragraphs[], bullets[]?] — English source text, translated below.
    $sections = [
        ['acceptance', 'Acceptance of these terms', [
            'These Terms and Conditions ("Terms") govern your use of the Infaqin website and services ("Infaqin", "we", "us"), operated by :legal_name. By creating an account, donating or otherwise using Infaqin, you agree to these Terms. If you do not agree, please do not use the service.',
        ]],
        ['definitions', 'Definitions', [], [
            '"Donor" means any person who makes a donation through Infaqin.',
            '"Campaigner" means a verified organisation or individual that raises funds through a campaign on Infaqin.',
            '"Campaign" means a fundraising page with a stated purpose, target amount and deadline.',
            '"Donation" means money given voluntarily by a Donor to a Campaign, including infaq, sedekah and zakat.',
            '"Open Fund Ledger" means the public record of every withdrawal made from a Campaign.',
        ]],
        ['accounts', 'Eligibility and your account', [
            'You must be at least 17 years old, or have permission from a parent or guardian, to create an account and donate.',
            'You agree to provide accurate information and keep it up to date. You are responsible for keeping your password confidential and for all activity under your account. Tell us immediately at :support_email if you suspect unauthorised use.',
            'One person may hold one account. Accounts may not be sold or transferred.',
        ]],
        ['donations', 'Donations', [
            'All donations are voluntary gifts. A donation does not give you any ownership, voting right or financial return in a Campaign.',
            'The minimum donation is :amount. A donation is recorded only once payment has been confirmed by the payment provider. Unpaid donations expire :hours hours after they are created and no money is taken.',
            'Infaqin does not charge donors a platform fee. Your receipt shows the amount, campaign, payment method and reference number, and is sent to the email address you provide.',
            'If you choose to donate anonymously, your name is shown publicly as "Hamba Allah". Infaqin and the Campaigner may still see your identity for legal, accounting and fraud-prevention purposes.',
        ]],
        ['refunds', 'Refunds', [
            'Donations are generally final. We will refund a donation in the following cases, if you contact us within 7 days with your reference number:',
        ], [
            'You were charged twice for the same donation, or charged an amount different from the one you confirmed.',
            'The Campaign is cancelled or found to be fraudulent before any funds have been withdrawn.',
            'A technical error on Infaqin caused the payment.',
        ]],
        ['zakat', 'Zakat', [
            'The zakat calculator is a guide to help you estimate zakat mal. It is not a religious ruling (fatwa). For specific circumstances, please consult a scholar or an official zakat institution.',
            'When you channel zakat through Infaqin, you are responsible for choosing a Campaign whose purpose fits the eligible recipients of zakat (asnaf).',
        ]],
        ['campaigners', 'Campaigners’ obligations', [
            'Campaigners must pass Infaqin’s verification and must:',
        ], [
            'Use donations only for the purpose described in the Campaign.',
            'Request withdrawals with a clear purpose and supporting evidence; every withdrawal is published on the Open Fund Ledger.',
            'Publish progress updates and a final report when the Campaign ends.',
            'Comply with Indonesian laws on public fundraising and charitable donations.',
        ]],
        ['transparency', 'Transparency and the Open Fund Ledger', [
            'Infaqin publishes the amount, date and purpose of every withdrawal made from a Campaign. Collected totals and donor counts are updated when payments are confirmed.',
            'If a Campaign does not reach its target, the funds collected are still used for its stated purpose and reported on the ledger, unless the Campaign is cancelled under the Refunds section.',
        ]],
        ['content', 'Prayers and user content', [
            'After donating, you may post a prayer or message on the Campaign. You remain responsible for what you post and grant Infaqin a non-exclusive licence to display it on the service.',
            'Content must be respectful. We may remove content that is hateful, abusive, misleading, spam, or that reveals another person’s private information.',
        ]],
        ['prohibited', 'Prohibited conduct', [
            'You agree not to:',
        ], [
            'Use Infaqin for money laundering, terrorist financing or any unlawful purpose.',
            'Use stolen payment methods or make donations you intend to reverse fraudulently.',
            'Impersonate another person or organisation, or create a misleading Campaign.',
            'Attempt to access other users’ accounts, interfere with the service, or scrape data without permission.',
        ]],
        ['privacy', 'Privacy and personal data', [
            'We collect the information you give us (such as your name, username, email, date of birth and phone number) and information about your donations. We use it to run your account, process donations, send receipts, prevent fraud and meet legal obligations.',
            'We never sell your personal data. We share it only with payment providers to process your payment, with Campaigners where needed to deliver your donation (your name is withheld if you donate anonymously), and with authorities when required by law.',
            'We process personal data in line with Indonesia’s Personal Data Protection Law (UU No. 27 Tahun 2022). You may ask to access, correct or delete your data by contacting :support_email. Some donation records must be kept for accounting and legal reasons.',
            'Passwords are stored using one-way hashing and the service uses encrypted connections.',
        ]],
        ['ip', 'Intellectual property', [
            'The Infaqin name, logo, design and software belong to :legal_name. Campaign images and stories belong to their Campaigners. You may share links to Campaigns, but you may not copy or reuse our branding without written permission.',
        ]],
        ['liability', 'Limitation of liability', [
            'Infaqin is a platform that connects Donors and Campaigners. We verify Campaigners and monitor withdrawals, but we do not guarantee the outcome of any Campaign.',
            'To the extent permitted by law, Infaqin is not liable for indirect or consequential losses, or for interruptions caused by payment providers, network failures or events beyond our reasonable control.',
        ]],
        ['termination', 'Suspension and termination', [
            'We may suspend or close accounts and Campaigns that break these Terms, are suspected of fraud, or where required by law. Withdrawals from a Campaign under investigation may be frozen until the investigation ends.',
            'You may close your account at any time by contacting us. Donations already made are not affected.',
        ]],
        ['changes', 'Changes to these terms', [
            'We may update these Terms from time to time. The date at the top of this page shows when they last changed. If the changes are significant, we will notify you by email or on the site before they take effect. Continuing to use Infaqin after that means you accept the updated Terms.',
        ]],
        ['law', 'Governing law', [
            'These Terms are governed by the laws of the Republic of Indonesia. We will first try to resolve any dispute amicably; if that fails, disputes will be settled in the District Court of Central Jakarta (Pengadilan Negeri Jakarta Pusat).',
        ]],
        ['contact', 'Contact us', [
            'Questions about these Terms can be sent to :email or by post to :legal_name, :address.',
        ]],
    ];

    $translate = fn (array $lines) => array_map(fn ($line) => __($line, $vars), $lines);
    $sections = array_map(fn ($s) => [$s[0], __($s[1]), $translate($s[2]), $translate($s[3] ?? [])], $sections);
@endphp

<x-layouts.app :title="__('Terms and Conditions')" :description="__('The terms that govern donating and fundraising on Infaqin, including refunds, privacy and campaigner obligations.')">
    <section class="bg-forest-900 text-white">
        <div class="container-page py-14">
            <p class="eyebrow text-mint">{{ __('Legal') }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Terms and Conditions') }}</h1>
            <p class="mt-3 text-sm text-white/70">{{ __('Last updated') }} <time datetime="{{ $updated->toDateString() }}">{{ $updated->translatedFormat('d F Y') }}</time></p>
        </div>
    </section>

    <div class="container-page grid gap-10 py-12 lg:grid-cols-[260px_1fr]">
        {{-- Table of contents --}}
        <aside>
            <nav class="card p-5 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto" aria-label="{{ __('Table of contents') }}"
                 x-data="{ current: '' }"
                 x-init="const spy = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) current = e.target.id }), { rootMargin: '-20% 0px -70% 0px' });
                         document.querySelectorAll('[data-terms-section]').forEach(el => spy.observe(el))">
                <p class="text-xs font-semibold tracking-wide text-ink/60 uppercase">{{ __('On this page') }}</p>
                <ol class="mt-3 space-y-1 text-sm">
                    @foreach ($sections as $n => [$id, $title])
                        <li>
                            <a href="#{{ $id }}" class="flex gap-2 rounded-lg px-2 py-1.5 transition hover:bg-mint-50 hover:text-brand"
                               :class="current === '{{ $id }}' ? 'bg-mint-50 font-medium text-brand' : 'text-ink'">
                                <span class="w-5 shrink-0 text-ink/40">{{ $n + 1 }}.</span> {{ $title }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </aside>

        {{-- Content --}}
        <article class="min-w-0">
            <div class="rounded-2xl border-l-4 border-brand bg-mint-50 p-5 text-sm leading-relaxed text-forest">
                <strong>{{ __('In short:') }}</strong> {{ __('donations are voluntary gifts, Infaqin charges donors no platform fee, every withdrawal is published, refunds are available for errors and cancelled campaigns, and we never sell your data. The full terms below are what apply.') }}
            </div>

            <div class="mt-8 space-y-10">
                @foreach ($sections as $n => $section)
                    @php([$id, $title, $paragraphs] = $section)
                    <section id="{{ $id }}" data-terms-section class="scroll-mt-24" aria-labelledby="{{ $id }}-title">
                        <h2 id="{{ $id }}-title" class="text-xl font-semibold text-night">{{ $n + 1 }}. {{ $title }}</h2>
                        <div class="mt-3 space-y-3 leading-relaxed text-ink">
                            @foreach ($paragraphs as $p)
                                <p>{{ $p }}</p>
                            @endforeach
                            @if (! empty($section[3]))
                                <ul class="space-y-2">
                                    @foreach ($section[3] as $item)
                                        <li class="flex gap-3"><x-icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-brand" /> <span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>

            <div class="mt-12 flex flex-col gap-3 border-t border-mist pt-8 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-ink/70">{{ __('Have a question that isn’t answered here?') }}</p>
                <a href="{{ route('faq') }}" class="btn-outline">{{ __('Read the FAQ') }} <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </article>
    </div>
</x-layouts.app>
