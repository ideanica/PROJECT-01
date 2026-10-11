<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8d9e2">
    <meta name="description" content="Ocean Paws — bringing your NCT WISH favorites closer.">
    <title>Ocean Paws — Bringing Your Favorites Closer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="op-body">
@php
    $loginDestination = auth()->user()?->member ? route('orders.index') : route('login');
    $tickerItems = ['ALBUM', 'LIGHTSTICK', 'KEYCHAIN', 'PHOTOCARD', 'POSTER', 'POB', 'PLUSHIE', 'TRADING CARD', 'FAN KIT', "SEASON'S GREETINGS"];
@endphp

<div class="op-page">
    <section class="op-hero" id="home">
        <img class="op-sky-cloud op-sky-cloud-a" src="{{ asset('assets/glossy_pastel_pink_cloud.png') }}" alt="" aria-hidden="true">
        <img class="op-sky-cloud op-sky-cloud-b" src="{{ asset('assets/pastel_pink_cloud_with_soft_highlights.png') }}" alt="" aria-hidden="true">

        <x-ocean-primary-navbar active="home" home-href="#home" mode="landing" />

        <div class="op-hero-scene">
            <img class="op-hero-wave" src="{{ asset('assets/ocean_paws_hero_ripples_wave.png') }}" alt="" aria-hidden="true">
            <img class="op-hero-cliff" src="{{ asset('assets/pastel_coastal_cliffs_and_greenery.png') }}" alt="" aria-hidden="true">
            <div class="op-copy-window-back" aria-hidden="true"></div>
            <div class="op-copy-window op-browser">
                <div class="op-browserbar"><strong>OCEANPAWS.EXE</strong><span class="op-window-dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
                <div class="op-copy-inner">
                    <div class="op-copy-main">
                        <h1>Where every <em>WISH</em> <br>comes a little closer
                            <svg class="op-heading-star op-heading-star-pink" viewBox="0 0 200 200" aria-hidden="true" focusable="false"><path d="M195 100c-87.305 4.275-90.725 7.695-95 95-4.275-87.305-7.695-90.725-95-95 87.305-4.275 90.725-7.695 95-95 4.275 87.305 7.695 90.725 95 95" /></svg>
                            <svg class="op-heading-star op-heading-star-green" viewBox="0 0 200 200" aria-hidden="true" focusable="false"><path d="M195 100c-87.305 4.275-90.725 7.695-95 95-4.275-87.305-7.695-90.725-95-95 87.305-4.275 90.725-7.695 95-95 4.275 87.305 7.695 90.725 95 95" /></svg>
                        </h1>
                        <p>Shop your NCT WISH favorites with us and <br>make every WISH a little more special <svg class="op-inline-heart" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg></p>
                        <div class="op-actions">
                            <a class="op-line" href="{{ $loginDestination }}"><svg xmlns="http://www.w3.org/2000/svg" class="op-login-icon" viewBox="0 0 640 640" width="24" height="24" fill="#355a45" aria-hidden="true" focusable="false" style="display:block;width:24px;height:24px;flex:0 0 24px;"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M409 337C418.4 327.6 418.4 312.4 409 303.1L265 159C258.1 152.1 247.8 150.1 238.8 153.8C229.8 157.5 224 166.3 224 176L224 256L112 256C85.5 256 64 277.5 64 304L64 336C64 362.5 85.5 384 112 384L224 384L224 464C224 473.7 229.8 482.5 238.8 486.2C247.8 489.9 258.1 487.9 265 481L409 337zM416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L480 544C533 544 576 501 576 448L576 192C576 139 533 96 480 96L416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160C497.7 160 512 174.3 512 192L512 448C512 465.7 497.7 480 480 480L416 480z"/></svg><span>{{ auth()->user()?->member ? 'Buka Pesanan' : 'Login Akun' }}</span></a>
                            <a class="op-search" href="{{ route('tracking.index') }}"><x-public-icon name="search" variant="rounded-bold" :size="24" /><span>Lacak Pesanan</span></a>
                        </div>
                    </div>
                    <div class="op-copy-hearts" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
                        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
                        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg>
                    </div>
                </div>
            </div>

            <img class="op-hero-mascot" src="{{ asset('assets/oceanpaws-hero-mascot-reference.png') }}" alt="Maskot Ocean Paws berselancar di ombak">
            <img class="op-hero-sand" src="{{ asset('assets/pastel_beach_sand_right.png') }}" alt="" aria-hidden="true">
            <img class="op-palm" src="{{ asset('assets/oceanpaws-palm-rooted-v3.png') }}" alt="" aria-hidden="true">
            <div class="op-sign" aria-label="NCT WISH, Oceanpaws, happier together">
                <img src="{{ asset('assets/oceanpaws-wooden-sign-v2.png') }}" alt="" aria-hidden="true">
                <span>NCT WISH</span>
                <span>OCEANPAWS</span>
                <span>HAPPIER<br>TOGETHER ♡</span>
            </div>
            <img class="op-sign-grass" src="{{ asset('assets/oceanpaws-hero-background-v3.png') }}" alt="" aria-hidden="true">
            <img class="op-hero-grass" src="{{ asset('assets/oceanpaws-sign-grass-v1.png') }}" alt="" aria-hidden="true">
            <img class="op-starfish" src="{{ asset('assets/glossy_pink_starfish_sticker.png') }}" alt="" aria-hidden="true">
        </div>
    </section>

    <div class="op-ticker" role="marquee" aria-label="K-pop merchandise: album, lightstick, keychain, photocard, poster, POB, plushie, trading card, fan kit, season's greetings">
        <div class="op-ticker-track" aria-hidden="true">
            @foreach ([1, 2] as $copy)
                <div class="op-ticker-group">
                    @foreach ($tickerItems as $item)
                        <span class="op-ticker-item">{{ $item }}</span><span class="op-ticker-separator">✦</span>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <main class="op-main">
        <section class="op-about" id="about" aria-labelledby="about-title">
            <article class="op-browser op-about-card">
                <div class="op-browserbar"><strong>ABOUT US</strong><span class="op-window-dots"><i></i><i></i><i></i></span></div>
                <div class="op-polaroids" aria-hidden="true">
                    <div class="op-polaroid op-polaroid-back"></div><div class="op-polaroid op-polaroid-mid"></div>
                    <div class="op-polaroid op-polaroid-front">
                        <header class="op-photo-titlebar"><strong>OCEAN PAWS.JPG</strong><span class="op-window-dots"><i></i><i></i><i></i></span></header>
                        <figure class="op-photo-screen"><img src="{{ asset('assets/oceanpaws-parcel-mascot-v2.webp') }}" alt=""></figure>
                        <footer class="op-photo-status"><span>Good Music · Brighter Days<br>with OCEANPAWS ♡</span><b>01 / 03</b></footer>
                    </div>
                </div>
                <div class="op-about-content">
                    <h2 id="about-title">Made for every <em>WISH</em>, since 2025. <svg class="op-inline-heart" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" /></svg></h2>
                    <p>Founded in June 2025, GO Oceanpaws is a group order dedicated to NCT WISH. Since then, we've grown into a little community of 2,000+ members and successfully handled thousands of batches together. From albums and photocards to merch and all the little WISH things you love, we're here to make every GO experience easier, clearer, and more enjoyable. ♡</p>
                    <div class="op-about-merch">
                        <img src="{{ asset('assets/oceanpaws-about-merch-collage-v1.png') }}" alt="" aria-hidden="true">
                    </div>
                </div>
            </article>
        </section>

        <section class="op-stats" aria-label="Statistik Ocean Paws">
            <article class="is-pink"><div class="op-stat-dots" aria-hidden="true"><i></i><i></i><i></i></div><div class="op-stat-inner"><img class="op-stat-icon" src="{{ asset('assets/stat-community.svg') }}" alt="" aria-hidden="true"><strong data-count="2000" data-count-suffix="+" aria-label="2,000+">2,000+</strong><span>Community Members</span></div></article>
            <article class="is-green"><div class="op-stat-dots" aria-hidden="true"><i></i><i></i><i></i></div><div class="op-stat-inner"><img class="op-stat-icon" src="{{ asset('assets/stat-batches.svg') }}" alt="" aria-hidden="true"><strong>Thousands+</strong><span>Batches Completed</span></div></article>
            <article class="is-pink"><div class="op-stat-dots" aria-hidden="true"><i></i><i></i><i></i></div><div class="op-stat-inner"><img class="op-stat-icon" src="{{ asset('assets/stat-wish.svg') }}" alt="" aria-hidden="true"><strong data-count="100" data-count-suffix="%" aria-label="100%">100%</strong><span>NCT WISH Focused</span></div></article>
            <article class="is-green"><div class="op-stat-dots" aria-hidden="true"><i></i><i></i><i></i></div><div class="op-stat-inner"><img class="op-stat-icon" src="{{ asset('assets/stat-secure.svg') }}" alt="" aria-hidden="true"><strong>Secure &amp; Clear</strong><span>Ordering Process</span></div></article>
        </section>

        {{-- Keep published testimonial data in the landing response for existing integrations,
             while the supplied reference intentionally has no visible testimonial panel. --}}
        <section hidden aria-hidden="true">
            @foreach ($testimonialSlides as $testimonial)
                <article><p>{{ $testimonial['content'] }}</p><span>{{ $testimonial['name'] }}</span></article>
            @endforeach
        </section>

        <section class="op-cta" aria-labelledby="cta-title">
            <img class="op-cta-wave" src="{{ asset('assets/pastel_mint_ocean_wave_banner.png') }}" alt="" aria-hidden="true">
            <img class="op-cta-mascot" src="{{ asset('assets/oceanpaws-cta-resting-mascot.png') }}" alt="Maskot Ocean Paws di pelampung">
            <div class="op-cta-copy"><h2 id="cta-title">Let's get closer<br><span>to your <em>WISH!</em></span></h2><a class="op-line" href="{{ $loginDestination }}"><svg xmlns="http://www.w3.org/2000/svg" class="op-login-icon" viewBox="0 0 640 640" width="24" height="24" fill="#355a45" aria-hidden="true" focusable="false" style="display:block;width:24px;height:24px;flex:0 0 24px;"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M409 337C418.4 327.6 418.4 312.4 409 303.1L265 159C258.1 152.1 247.8 150.1 238.8 153.8C229.8 157.5 224 166.3 224 176L224 256L112 256C85.5 256 64 277.5 64 304L64 336C64 362.5 85.5 384 112 384L224 384L224 464C224 473.7 229.8 482.5 238.8 486.2C247.8 489.9 258.1 487.9 265 481L409 337zM416 480C398.3 480 384 494.3 384 512C384 529.7 398.3 544 416 544L480 544C533 544 576 501 576 448L576 192C576 139 533 96 480 96L416 96C398.3 96 384 110.3 384 128C384 145.7 398.3 160 416 160L480 160C497.7 160 512 174.3 512 192L512 448C512 465.7 497.7 480 480 480L416 480z"/></svg><span>{{ auth()->user()?->member ? 'Buka Pesanan' : 'Login Akun' }}</span></a></div>
            <img class="op-cta-palm" src="{{ asset('assets/kawaii_pastel_palm_tree_sticker.png') }}" alt="" aria-hidden="true">
            <div class="op-cta-sign"><img src="{{ asset('assets/oceanpaws-cta-wood-sign-v1.png') }}" alt="" aria-hidden="true"><span>NCT WISH<br><b>A BRIGHTER<br>TOMORROW<br>TOGETHER ♡</b></span></div>
            <img class="op-cta-sand" src="{{ asset('assets/oceanpaws-cta-sand-only-v2.png') }}" alt="" aria-hidden="true">
            <img class="op-cta-starfish" src="{{ asset('assets/oceanpaws-cta-starfish-3d-v1.png') }}" alt="" aria-hidden="true">
        </section>


        <div class="op-mobile-tracking-wrap">
            <a class="op-mobile-tracking" href="{{ route('tracking.index') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="op-tracking-search-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9f345c" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="display:block;width:28px;height:28px;flex:0 0 28px;color:#9f345c;stroke:#9f345c;"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>
                <span><strong>Still looking for your order?</strong><small>Track it here!</small></span>
            </a>
        </div>
    </main>

    <style>
        @media (max-width: 1023px) {
            .op-page .op-cta-copy .op-line {
                width: calc(100% - 20px) !important;
                min-width: 0 !important;
                max-width: calc(100% - 20px) !important;
                box-sizing: border-box;
                align-self: center;
            }
            .op-page .op-cta-copy h2 {
                margin-bottom: calc(clamp(5px, .9vw, 12px) + 10px) !important;
            }
            .op-page .op-cta-mascot {
                left: calc(-6% - 20px) !important;
            }
        }
        .op-footer-reference{
            position:relative;
            isolation:isolate;
            min-height:330px;
            overflow:hidden;
            background:#f7f3e9;
            color:#315d48;
            font-family:"Nunito Sans",sans-serif;
        }
        .op-footer-reference::before{
            content:"";
            position:absolute;
            z-index:1;
            top:0;
            left:-2%;
            width:104%;
            height:38px;
            border-top:3px solid #74a884;
            border-radius:50% 50% 0 0/100% 100% 0 0;
            transform:translateY(8px) rotate(-.35deg);
            pointer-events:none;
        }
        .op-footer-reference .op-footer-reference-shell{
            position:relative;
            z-index:4;
            display:grid;
            width:min(100% - 80px,1240px);
            margin:0 auto;
            grid-template-columns:minmax(235px,1.05fr) auto minmax(230px,.95fr);
            align-items:start;
            gap:34px;
            padding:54px 0 145px;
        }
        .op-footer-reference .op-footer-brand-block{
            min-width:0;
        }
        .op-footer-reference .op-footer-brand-block img{
            display:block;
            width:220px;
            max-width:100%;
            height:auto;
            margin-left:-5px;
        }
        .op-footer-reference .op-footer-brand-block p{
            margin:3px 0 0;
            color:#3f7058;
            font-size:13px;
            font-weight:600;
            letter-spacing:.01em;
        }
        .op-footer-reference .op-footer-center{
            display:flex;
            align-items:flex-start;
            gap:30px;
            padding-top:29px;
        }
        .op-footer-reference .op-footer-links,
        .op-footer-reference .op-footer-socials{
            display:flex;
            align-items:center;
            gap:22px;
        }
        .op-footer-reference .op-footer-links{
            padding:0 29px;
            border-right:1px solid rgba(49,93,72,.48);
            border-left:1px solid rgba(49,93,72,.48);
        }
        .op-footer-reference .op-footer-links a,
        .op-footer-reference .op-footer-socials a{
            color:#315d48;
            text-decoration:none;
            transition:transform .18s ease,opacity .18s ease;
        }
        .op-footer-reference .op-footer-links a{
            font-size:13px;
            font-weight:700;
            white-space:nowrap;
        }
        .op-footer-reference .op-footer-socials a{
            display:grid;
            width:24px;
            height:24px;
            place-items:center;
            font-size:18px;
        }
        .op-footer-reference .op-footer-links a:hover,
        .op-footer-reference .op-footer-socials a:hover{
            opacity:.72;
            transform:translateY(-2px);
        }
        .op-footer-reference .op-footer-message{
            position:relative;
            display:flex;
            min-width:0;
            justify-self:end;
            align-items:flex-start;
            gap:22px;
            padding-top:24px;
        }
        .op-footer-reference .op-footer-message strong{
            display:block;
            color:#315d48;
            font-family:"Nunito Sans",sans-serif;
            font-size:18px;
            font-weight:800;
            line-height:1.35;
            letter-spacing:-.02em;
            white-space:nowrap;
        }
        .op-footer-reference .op-footer-message strong span{
            display:block;
        }
        .op-footer-reference .op-footer-shell-icon{
            width:62px;
            height:56px;
            flex:none;
            transform:rotate(7deg);
            filter:drop-shadow(0 3px 0 rgba(137,73,94,.08));
        }
        .op-footer-reference .op-footer-waves{
            position:absolute;
            z-index:2;
            right:0;
            bottom:0;
            left:0;
            height:185px;
            pointer-events:none;
        }
        .op-footer-reference .op-footer-waves svg{
            position:absolute;
            inset:auto 0 0;
            width:100%;
            height:100%;
            display:block;
        }
        .op-footer-reference .op-footer-cloud{
            position:absolute;
            z-index:3;
            bottom:83px;
            width:160px;
            height:68px;
            opacity:.92;
            pointer-events:none;
        }
        .op-footer-reference .op-footer-cloud.is-left{left:-20px}
        .op-footer-reference .op-footer-cloud.is-right{right:-26px;transform:scaleX(-1)}
        .op-footer-reference .op-footer-bottomline{
            position:absolute;
            z-index:5;
            right:max(26px,calc((100% - 1240px)/2));
            bottom:25px;
            left:max(26px,calc((100% - 1240px)/2));
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            color:#f4fbf4;
            font-size:11px;
            font-weight:700;
            letter-spacing:.01em;
            text-shadow:0 1px 1px rgba(44,83,56,.18);
        }
        @media(max-width:980px){
            .op-footer-reference{min-height:405px}
            .op-footer-reference .op-footer-reference-shell{
                width:min(100% - 48px,900px);
                grid-template-columns:1fr 1fr;
                gap:20px 34px;
                padding-top:50px;
            }
            .op-footer-reference .op-footer-center{
                grid-column:1/-1;
                grid-row:2;
                justify-content:flex-start;
                padding-top:0;
            }
            .op-footer-reference .op-footer-message{
                grid-column:2;
                grid-row:1;
            }
        }
        @media(max-width:680px){
            .op-footer-reference{min-height:510px}
            .op-footer-reference .op-footer-reference-shell{
                width:min(100% - 36px,560px);
                grid-template-columns:1fr;
                gap:20px;
                padding:44px 0 175px;
            }
            .op-footer-reference .op-footer-brand-block img{width:190px}
            .op-footer-reference .op-footer-center,
            .op-footer-reference .op-footer-message{
                grid-column:1;
                grid-row:auto;
                justify-self:start;
            }
            .op-footer-reference .op-footer-center{
                flex-wrap:wrap;
                gap:18px;
            }
            .op-footer-reference .op-footer-links{
                gap:16px;
                padding:0 18px;
                border-left:0;
            }
            .op-footer-reference .op-footer-socials{gap:16px}
            .op-footer-reference .op-footer-message{
                padding-top:4px;
            }
            .op-footer-reference .op-footer-message strong{
                font-size:16px;
            }
            .op-footer-reference .op-footer-waves{height:180px}
            .op-footer-reference .op-footer-bottomline{
                right:18px;
                bottom:20px;
                left:18px;
                align-items:flex-start;
                flex-direction:column;
                gap:4px;
                font-size:10px;
            }
        }
        @media(max-width:430px){
            .op-footer-reference{min-height:535px}
            .op-footer-reference .op-footer-reference-shell{padding-top:38px}
            .op-footer-reference .op-footer-center{display:block}
            .op-footer-reference .op-footer-links{
                width:100%;
                justify-content:space-between;
                gap:10px;
                padding:0 0 15px;
                border:0;
                border-bottom:1px solid rgba(49,93,72,.35);
            }
            .op-footer-reference .op-footer-links a{font-size:12px}
            .op-footer-reference .op-footer-socials{
                margin-top:15px;
                justify-content:flex-start;
            }
            .op-footer-reference .op-footer-shell-icon{width:54px;height:48px}
        }

        /* Match the supplied 1352 × 183 footer; keep its text and links live. */
        .op-footer-reference{
            display:block;
            height:183px;
            min-height:183px;
            border:0;
            background:#f7f3e9 url('{{ asset('assets/oceanpaws-footer-reference-bg-v1.png') }}') center 40%/100% 136% no-repeat;
        }
        .op-footer-reference::before{content:none}
        .op-footer-reference .op-footer-reference-shell{
            position:absolute;
            top:39px;
            left:50%;
            display:grid;
            width:min(calc(100% - 136px),1216px);
            height:78px;
            grid-template-columns:202px 288px 200px minmax(0,1fr);
            align-items:center;
            gap:0;
            margin:0;
            padding:0;
            transform:translateX(-50%);
        }
        .op-footer-reference .op-footer-brand-block img{width:180px;margin:0 0 0 -7px}
        .op-footer-reference .op-footer-center{
            display:grid;
            width:auto;
            height:26px;
            grid-column:2 / span 2;
            grid-template-columns:288px 200px;
            align-items:center;
            gap:0;
            padding:0;
        }
        .op-footer-reference .op-footer-links,
        .op-footer-reference .op-footer-socials{
            height:24px;
            justify-content:space-between;
            gap:0;
        }
        .op-footer-reference .op-footer-links{
            padding:0 29px;
            border-right:1px solid #52765f;
            border-left:1px solid #52765f;
        }
        .op-footer-reference .op-footer-socials{
            padding:0 30px;
            border-right:1px solid #52765f;
        }
        .op-footer-reference .op-footer-links a{
            font:500 11px/24px "Nunito Sans",sans-serif;
        }
        .op-footer-reference .op-footer-socials a{
            width:24px;
            height:24px;
            font-size:19px;
        }
        .op-footer-reference .op-footer-message{
            display:flex;
            height:72px;
            grid-column:4;
            align-items:center;
            justify-self:end;
            gap:15px;
            padding:0;
        }
        .op-footer-reference .op-footer-message strong{
            font:400 11px/1.35 ui-monospace,"Courier New",monospace;
            letter-spacing:0;
        }
        .op-footer-reference .op-footer-shell-icon{
            display:block;
            width:65px;
            height:65px;
            object-fit:contain;
            transform:rotate(-4deg);
            filter:none;
        }
        .op-footer-reference .op-footer-bottomline{
            right:auto;
            bottom:14px;
            left:0;
            width:100%;
            align-items:center;
            justify-content:center;
            flex-direction:row;
            text-align:center;
            font:400 10px/1.2 ui-monospace,"Courier New",monospace;
            letter-spacing:0;
            text-shadow:none;
        }
        @media(max-width:1160px){
            .op-footer-reference{height:265px;min-height:265px;background-size:100% 115%}
            .op-footer-reference .op-footer-reference-shell{
                top:39px;
                width:calc(100% - 64px);
                height:auto;
                grid-template-columns:1fr auto;
                grid-template-rows:72px 38px;
                row-gap:7px;
            }
            .op-footer-reference .op-footer-brand-block{grid-column:1;grid-row:1}
            .op-footer-reference .op-footer-center{
                width:max-content;
                grid-column:1 / -1;
                grid-row:2;
            }
            .op-footer-reference .op-footer-message{
                grid-column:2;
                grid-row:1;
                padding-left:0;
            }
            .op-footer-reference .op-footer-bottomline{left:0;width:100%}
        }
        @media(max-width:620px){
            .op-footer-reference{height:340px;min-height:340px;background-size:auto 100%;background-position:center}
            .op-footer-reference .op-footer-reference-shell{
                top:34px;
                width:calc(100% - 36px);
                display:flex;
                height:auto;
                flex-direction:column;
                align-items:flex-start;
                gap:16px;
            }
            .op-footer-reference .op-footer-brand-block img{width:165px;margin-left:0}
            .op-footer-reference .op-footer-center{
                display:flex;
                width:100%;
                height:auto;
                flex-wrap:wrap;
                gap:12px;
            }
            .op-footer-reference .op-footer-links{
                display:grid;
                width:100%;
                height:28px;
                grid-template-columns:repeat(4,minmax(0,1fr));
                padding:0 12px;
                border-right:0;
                border-bottom:1px solid #52765f;
                border-left:0;
            }
            .op-footer-reference .op-footer-links a{font-size:10px;text-align:center}
            .op-footer-reference .op-footer-socials{
                width:100%;
                max-width:190px;
                padding:0;
                border:0;
            }
            .op-footer-reference .op-footer-message{height:48px;gap:8px;padding:0}
            .op-footer-reference .op-footer-message strong{font-size:11px}
            .op-footer-reference .op-footer-shell-icon{width:48px;height:48px}
            .op-footer-reference .op-footer-bottomline{
                bottom:12px;
                left:0;
                width:100%;
                align-items:center;
                flex-direction:row;
                font-size:9px;
            }
        }
        .op-footer-mobile-links{display:none}
        @media(max-width:1023px){
            .op-footer-reference{
                height:292px;
                min-height:292px;
                background-size:auto 100%;
                background-position:center bottom;
            }
            .op-footer-reference .op-footer-reference-shell{
                top:93px;
                left:18px;
                display:grid;
                width:calc(100% - 36px);
                height:auto;
                grid-template-columns:minmax(0,1fr) auto;
                align-items:center;
                gap:12px;
                padding:0;
                transform:none;
            }
            .op-footer-reference .op-footer-brand-block{
                display:flex;
                min-width:0;
                justify-content:flex-start;
                grid-column:1;
            }
            .op-footer-reference .op-footer-brand-block img{
                width:clamp(118px,36vw,148px);
                margin:0;
            }
            .op-footer-reference .op-footer-center,
            .op-footer-reference .op-footer-links,
            .op-footer-reference .op-footer-mobile-links,
            .op-footer-reference .op-footer-socials{
                display:none;
            }
            .op-footer-reference .op-footer-message{
                position:static;
                display:flex;
                width:auto;
                height:auto;
                grid-column:2;
                align-items:center;
                justify-self:end;
                gap:7px;
                padding:0;
                transform:none;
                text-align:left;
                white-space:nowrap;
            }
            .op-footer-reference .op-footer-message strong{
                display:grid;
                gap:1px;
                font-size:clamp(7px,2.15vw,9px);
                line-height:1.25;
                text-align:left;
            }
            .op-footer-reference .op-footer-shell-icon{
                display:block;
                width:clamp(32px,9vw,40px);
                height:clamp(32px,9vw,40px);
            }
            .op-footer-reference .op-footer-bottomline{
                right:0;
                bottom:12px;
                left:0;
                width:100%;
                align-items:center;
                justify-content:center;
                color:#f8fff8;
                font-size:8px;
                text-align:center;
            }
        }

        /* Continue the sand grid through the footer until the shoreline begins. */
        @media(max-width:1023px){
            .op-footer-reference::after{
                content:"";
                position:absolute;
                z-index:2;
                top:0;
                right:0;
                left:0;
                height:72%;
                pointer-events:none;
                background-image:
                    linear-gradient(rgba(211,190,151,.075) 1px,transparent 1px),
                    linear-gradient(90deg,rgba(211,190,151,.075) 1px,transparent 1px);
                background-size:38px 38px;
                background-position:0 0;
            }
        }

        @media(max-width:380px){
            .op-footer-reference .op-footer-reference-shell{
                left:14px;
                width:calc(100% - 28px);
                gap:8px;
            }
            .op-footer-reference .op-footer-brand-block img{
                width:112px;
            }
            .op-footer-reference .op-footer-message{
                gap:5px;
            }
            .op-footer-reference .op-footer-shell-icon{
                width:30px;
                height:30px;
            }
        }
    </style>

    <footer class="op-footer op-footer-reference" id="footer">
        <div class="op-footer-reference-shell">
            <div class="op-footer-brand-block">
                <a href="#home" aria-label="Ocean Paws — kembali ke atas">
                    <img src="{{ asset('assets/oceanpaws-logo-no-sticker.png') }}" alt="Ocean Paws">
                </a>
            </div>

            <div class="op-footer-message">
                <strong><span>Small Orders</span><span>Bigger Happiness ♡</span></strong>
                <img class="op-footer-shell-icon" src="{{ asset('assets/oceanpaws-footer-shell-v1.png') }}" alt="" aria-hidden="true">
            </div>
        </div>

        <div class="op-footer-bottomline">
            <span>© 2025 OCEANPAWS. All rights reserved.</span>
        </div>
    </footer>
</div>
</body>
</html>
