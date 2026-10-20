<?php

return [

    // Languages the site can be shown in (code => name in that language).
    'locales' => [
        'en' => 'English',
        'id' => 'Bahasa Indonesia',
    ],

    'company' => [
        'name' => 'Infaqin',
        'legal_name' => 'PT Infaqin Kebaikan Nusantara',
        'tagline' => 'Every rupiah, a reason to hope.',
        'email' => 'hello@infaqin.com',
        'support_email' => 'support@infaqin.com',
        'phone' => '+62 21 5555 0123',
        'whatsapp' => '+62 812 0000 1234',
        'address' => 'Jl. Kebaikan No. 12, Menteng, Jakarta Pusat 10310, Indonesia',
        'hours' => 'Mon – Fri, 08.00 – 17.00 WIB',
        'socials' => [
            'instagram' => 'https://instagram.com/infaqin',
            'x' => 'https://x.com/infaqin',
            'facebook' => 'https://facebook.com/infaqin',
            'youtube' => 'https://youtube.com/@infaqin',
            'tiktok' => 'https://tiktok.com/@infaqin',
        ],
    ],

    'vision' => 'A world where no one in need is left unseen — where every act of giving travels straight, transparently and joyfully to the people it was meant for.',

    'missions' => [
        'Connect donors directly with verified campaigners and the communities they serve.',
        'Make every withdrawal of every campaign public, so trust is earned, not assumed.',
        'Turn giving into a habit of the heart through prayer, reminders and shared stories.',
        'Keep platform costs low so more of every rupiah reaches the people who need it.',
    ],

    // Features that set Infaqin apart from a typical donation page.
    'features' => [
        [
            'key' => 'ledger',
            'title' => 'Open Fund Ledger',
            'body' => 'Every cash-out from every campaign is published with its amount, date and purpose. You see exactly how often and why money left the campaign.',
        ],
        [
            'key' => 'prayer',
            'title' => 'Prayer Wall & Aamiin',
            'body' => 'After donating, leave a prayer for the people you helped. Others can answer it with a single "Aamiin" — giving becomes a shared act of hope.',
        ],
        [
            'key' => 'zakat',
            'title' => 'Built-in Zakat Calculator',
            'body' => 'Work out your zakat mal against the current gold nisab in seconds, then channel it to a campaign in one tap.',
        ],
        [
            'key' => 'streak',
            'title' => 'Kindness Streak',
            'body' => 'Track the weeks in a row you have given. A gentle nudge that turns one-off generosity into a lasting habit.',
        ],
        [
            'key' => 'tribute',
            'title' => 'Give on Behalf Of',
            'body' => 'Dedicate a donation to your parents, a friend, or someone who has passed. Their name travels with your gift on the receipt.',
        ],
        [
            'key' => 'anonymous',
            'title' => 'Hamba Allah Mode',
            'body' => 'One switch hides your name everywhere public. Your receipt and history stay private to you — sincerity without the spotlight.',
        ],
    ],

    // Placeholder personas — replace with real, consented endorsements before launch.
    'testimonials' => [
        [
            'name' => 'Rania Putri',
            'role' => 'Actress & Humanitarian Ambassador',
            'quote' => 'The Open Fund Ledger changed how I give. I can see every withdrawal and what it paid for. That kind of honesty is rare.',
        ],
        [
            'name' => 'Ustadz Fikri Hamdan',
            'role' => 'Preacher & Author',
            'quote' => 'Infaqin brings back the spirit of sedekah: sincere, quiet, and full of doa. The prayer wall moves me every time I read it.',
        ],
        [
            'name' => 'Dimas Prakoso',
            'role' => 'National Team Footballer',
            'quote' => 'I keep a kindness streak the same way I keep a training streak. Small, consistent, every week. It adds up.',
        ],
        [
            'name' => 'Nadia Halim',
            'role' => 'Tech Entrepreneur',
            'quote' => 'Clean, fast and transparent. Donating takes less than a minute and I always know where my money went.',
        ],
    ],

    'donation_presets' => [25_000, 50_000, 100_000, 250_000, 500_000, 1_000_000],

    'terms_updated_at' => '2026-10-18',

    'faq' => [
        'general' => [
            'label' => 'General',
            'items' => [
                ['What is Infaqin?', 'Infaqin is a donation platform that connects donors with verified campaigners helping people in need — from mosques and Qur\'an distribution to disaster relief, education and health. Every withdrawal a campaign makes is published on its Open Fund Ledger.'],
                ['Who runs the campaigns?', 'Campaigns are run by foundations, community organisations and volunteer teams that have passed our verification. A green shield next to a campaigner\'s name means their identity and legal documents have been checked.'],
                ['Do I need an account to donate?', 'You can browse every campaign without an account. To donate, you need a free account so we can send your receipt, keep your donation history and let you leave a prayer.'],
                ['Is Infaqin only for Muslims?', 'No. Infaqin is built around the values of infaq, sedekah and zakat, but anyone who wants to help is welcome to give.'],
            ],
        ],
        'donations' => [
            'label' => 'Donations & payment',
            'items' => [
                ['What is the minimum donation?', 'The minimum donation is Rp10.000. There is no maximum for everyday giving, but single transactions above Rp1.000.000.000 must be arranged with our team.'],
                ['Which payment methods can I use?', 'QRIS, GoPay, OVO, DANA and virtual accounts from BCA, Mandiri and BNI. More methods will be added over time.'],
                ['Does Infaqin take a fee from my donation?', 'No. Infaqin charges no platform fee — the full amount you enter goes to the campaign, as shown in the summary before you pay.'],
                ['How long do I have to complete a payment?', 'A pending donation stays open for 24 hours. After that it expires and no money is taken. You can always find pending donations in your History and pay or cancel them there.'],
                ['Can I get a refund?', 'Donations are generally final once paid. We do refund duplicate payments, amounts charged by mistake, and donations to campaigns that are cancelled before any funds are withdrawn. Contact support within 7 days with your reference number.'],
                ['Can I donate on behalf of someone else?', 'Yes. Fill in "Donate on behalf of" on the payment page — for example a parent or someone who has passed away. Their name appears on your receipt.'],
            ],
        ],
        'transparency' => [
            'label' => 'Transparency',
            'items' => [
                ['What is the Open Fund Ledger?', 'Every time a campaigner withdraws money, the amount, date and purpose are published on the campaign page. You can see exactly how many times a campaign has been cashed out and why.'],
                ['How do I know my money reached the campaign?', 'Your receipt shows the campaign, amount and reference number, and the campaign\'s collected total updates as soon as your payment is confirmed. Withdrawals from that total appear on the ledger.'],
                ['What happens if a campaign does not reach its target?', 'Funds collected still go to the campaign\'s purpose. Campaigners must explain on the ledger how the collected amount was used, even when the target is not met.'],
                ['What if I suspect a campaign is fraudulent?', 'Email support@infaqin.com with the campaign link. We freeze withdrawals while we investigate, and campaigns found to be fraudulent are closed and reported to the authorities.'],
            ],
        ],
        'zakat' => [
            'label' => 'Zakat & sedekah',
            'items' => [
                ['How does the zakat calculator work?', 'It adds up your cash, gold, investments and receivables, subtracts debts due now, and compares the result to the nisab of 85 grams of gold. If your wealth has reached the nisab and been held for a year, zakat is 2.5% of it.'],
                ['Is the calculator result a religious ruling?', 'No. It is a guide to help you estimate your zakat mal. For specific situations, please consult a scholar or an official zakat institution.'],
                ['Can I pay zakat through Infaqin?', 'You can channel your zakat to any campaign whose purpose fits the eligible recipients (asnaf). Choose the campaign, enter your zakat amount and mention "zakat" in your prayer if you wish.'],
            ],
        ],
        'account' => [
            'label' => 'Account & privacy',
            'items' => [
                ['How does anonymous ("Hamba Allah") donation work?', 'Turn on the anonymous switch when donating. Your name is replaced with "Hamba Allah" in donor lists and prayers. The donation still appears in your own private history and receipt.'],
                ['Why should I verify my email?', 'Verifying your email proves the address belongs to you, protects your account and makes sure receipts reach you. Open Profile → Email & verification and send yourself a link.'],
                ['How do I change my password?', 'Go to Profile → Change password. You need your current password, and the new one must have at least 8 characters with upper and lower case letters, a number and a symbol.'],
                ['Who can see my personal data?', 'Only you and authorised Infaqin staff. We never sell your data, and campaigners only see your name if you did not donate anonymously. See the Privacy section of our Terms and Conditions.'],
            ],
        ],
        'campaigners' => [
            'label' => 'For campaigners',
            'items' => [
                ['How can my organisation start a campaign?', 'Email partners@infaqin.com with your organisation\'s legal documents, the campaign plan and a budget. Our team reviews every application, usually within 5 working days.'],
                ['How do withdrawals work?', 'Campaigners request a withdrawal with its purpose and supporting documents. Once approved, the funds are transferred and the withdrawal is published on the Open Fund Ledger.'],
                ['What reports do campaigners need to provide?', 'A short update with photos for every withdrawal, and a final report when the campaign ends. Campaigners who do not report cannot make further withdrawals.'],
            ],
        ],
    ],

];
