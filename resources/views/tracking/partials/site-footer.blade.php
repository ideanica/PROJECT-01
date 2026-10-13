<style>
    /* Exact visual copy of the current Landing footer. */
    .ocean-tracking .op-footer-reference{
        position:relative;
        isolation:isolate;
        display:block;
        height:183px;
        min-height:183px;
        overflow:hidden;
        border:0;
        background:#f7f3e9 url('{{ asset('assets/oceanpaws-footer-reference-bg-v1.png') }}') center 40%/100% 136% no-repeat;
        color:#315d48;
        font-family:"Nunito Sans",sans-serif;
    }
    .ocean-tracking .op-footer-reference::before{content:none}
    .ocean-tracking .op-footer-reference .op-footer-reference-shell{
        position:absolute;
        z-index:4;
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
    .ocean-tracking .op-footer-reference .op-footer-brand-block img{
        display:block;
        width:180px;
        height:auto;
        margin:0 0 0 -7px;
    }
    .ocean-tracking .op-footer-reference .op-footer-message{
        display:flex;
        height:72px;
        grid-column:4;
        align-items:center;
        justify-self:end;
        gap:15px;
        padding:0;
    }
    .ocean-tracking .op-footer-reference .op-footer-message strong{
        font:400 11px/1.35 ui-monospace,"Courier New",monospace;
        letter-spacing:0;
    }
    .ocean-tracking .op-footer-reference .op-footer-message strong span{display:block}
    .ocean-tracking .op-footer-reference .op-footer-shell-icon{
        display:block;
        width:65px;
        height:65px;
        object-fit:contain;
        transform:rotate(-4deg);
        filter:none;
    }
    .ocean-tracking .op-footer-reference .op-footer-bottomline{
        position:absolute;
        z-index:4;
        right:auto;
        bottom:14px;
        left:0;
        display:flex;
        width:100%;
        align-items:center;
        justify-content:center;
        flex-direction:row;
        color:#f8fff8;
        text-align:center;
        font:400 10px/1.2 ui-monospace,"Courier New",monospace;
        letter-spacing:0;
        text-shadow:none;
    }

    @media(max-width:1160px){
        .ocean-tracking .op-footer-reference{
            height:265px;
            min-height:265px;
            background-size:100% 115%;
        }
        .ocean-tracking .op-footer-reference .op-footer-reference-shell{
            top:39px;
            width:calc(100% - 64px);
            height:auto;
            grid-template-columns:1fr auto;
            grid-template-rows:72px 38px;
            row-gap:7px;
        }
        .ocean-tracking .op-footer-reference .op-footer-brand-block{grid-column:1;grid-row:1}
        .ocean-tracking .op-footer-reference .op-footer-message{
            grid-column:2;
            grid-row:1;
            padding-left:0;
        }
        .ocean-tracking .op-footer-reference .op-footer-bottomline{left:0;width:100%}
    }

    @media(max-width:1023px){
        .ocean-tracking .op-footer-reference{
            height:292px;
            min-height:292px;
            margin-top:0;
            background-size:auto 100%;
            background-position:center bottom;
        }
        .ocean-tracking .op-footer-reference .op-footer-reference-shell{
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
        .ocean-tracking .op-footer-reference .op-footer-brand-block{
            display:flex;
            min-width:0;
            justify-content:flex-start;
            grid-column:1;
        }
        .ocean-tracking .op-footer-reference .op-footer-brand-block img{
            width:clamp(118px,36vw,148px);
            margin:0;
        }
        .ocean-tracking .op-footer-reference .op-footer-message{
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
        .ocean-tracking .op-footer-reference .op-footer-message strong{
            display:grid;
            gap:1px;
            font-size:clamp(7px,2.15vw,9px);
            line-height:1.25;
            text-align:left;
        }
        .ocean-tracking .op-footer-reference .op-footer-shell-icon{
            display:block;
            width:clamp(32px,9vw,40px);
            height:clamp(32px,9vw,40px);
        }
        .ocean-tracking .op-footer-reference .op-footer-bottomline{
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
        .ocean-tracking .op-footer-reference::after{
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
        .ocean-tracking .op-footer-reference .op-footer-reference-shell{
            left:14px;
            width:calc(100% - 28px);
            gap:8px;
        }
        .ocean-tracking .op-footer-reference .op-footer-brand-block img{width:112px}
        .ocean-tracking .op-footer-reference .op-footer-message{gap:5px}
        .ocean-tracking .op-footer-reference .op-footer-shell-icon{
            width:30px;
            height:30px;
        }
    }

    /*
     * Dark Tracking: only the paper area before the green footer flow becomes
     * dark. The green/mint footer artwork, logo, slogan, shell and copyright
     * keep their original Landing colors.
     */
    html[data-customer-theme="dark"] body.ocean-tracking .op-footer-reference::before{
        content:"";
        position:absolute;
        z-index:3;
        top:0;
        right:0;
        left:0;
        height:29%;
        pointer-events:none;
        background:
            linear-gradient(rgba(189,221,194,.07) 1px,transparent 1px),
            linear-gradient(90deg,rgba(189,221,194,.07) 1px,transparent 1px),
            #20382e;
        background-size:42px 42px;
        background-position:0 0;
        clip-path:polygon(
            0 0,
            100% 0,
            100% 79%,
            94% 78%,
            88% 77%,
            82% 78%,
            75% 81%,
            69% 86%,
            63% 92%,
            56% 94%,
            50% 92%,
            44% 87%,
            38% 83%,
            31% 80%,
            25% 80%,
            19% 82%,
            13% 84%,
            6% 83%,
            0 81%
        );
    }

    html[data-customer-theme="dark"] body.ocean-tracking .op-footer-reference::after{
        content:none !important;
    }

</style>

<footer class="op-footer op-footer-reference" id="footer">
    <div class="op-footer-reference-shell">
        <div class="op-footer-brand-block">
            <a href="{{ route('home') }}" aria-label="Ocean Paws — kembali ke beranda">
                <img src="{{ asset('assets/oceanpaws-logo-no-sticker.png') }}" alt="Ocean Paws">
            </a>
        </div>

        <div class="op-footer-message">
            <strong><span>Small Orders</span><span>Bigger Happiness ♡</span></strong>
            <img class="op-footer-shell-icon" src="{{ asset('assets/oceanpaws-footer-shell-v1.png') }}" alt="" aria-hidden="true">
        </div>
    </div>

    <div class="op-footer-bottomline">
        <span>© {{ now()->year }} OCEANPAWS. All rights reserved.</span>
    </div>
</footer>
