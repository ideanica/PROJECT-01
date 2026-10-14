<style>
    html[data-customer-theme="dark"] body.ocean-profile {
        --profile-ink: #edf6ef;
        --profile-outline: #78977f;
        --profile-pink: #563642;
        --profile-pink-strong: #ef86a3;
        --profile-mint: #31483a;
        --profile-paper: #1b3027;
        --profile-muted: #aec0b4;
        color-scheme: dark;
        background:
            linear-gradient(rgba(151,181,159,.055) 1px, transparent 1px),
            linear-gradient(90deg, rgba(151,181,159,.055) 1px, transparent 1px),
            #12251e;
        background-size: 42px 42px;
        color: var(--profile-ink);
    }

    html[data-customer-theme="dark"] .ocean-profile .customer-public-shell,
    html[data-customer-theme="dark"] .ocean-profile .public-landing-main,
    html[data-customer-theme="dark"] .ocean-profile .profile-page,
    html[data-customer-theme="dark"] .profile-site-header {
        background: transparent !important;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-browser {
        border-color: #78977f;
        background: #1b3027;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-browserbar {
        border-bottom-color: #78977f;
        background: #2b4336;
        color: #dce9df;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-navrow {
        background: #1b3027;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-nav a,
    html[data-customer-theme="dark"] .profile-site-header .op-nav a i {
        color: #c4d4c9;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-nav a:hover,
    html[data-customer-theme="dark"] .profile-site-header .op-nav a.is-active,
    html[data-customer-theme="dark"] .profile-site-header .op-nav a.is-active i {
        color: #f08aa5;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-nav a.is-active::after {
        background: #f08aa5;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-main,
    html[data-customer-theme="dark"] .ocean-profile .section-card,
    html[data-customer-theme="dark"] .ocean-profile .calendar-card,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-card {
        border-color: #78977f !important;
        background: #1b3027 !important;
        color: #edf6ef !important;
        box-shadow: var(--customer-shadow-card) #081810 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-top {
        border-color: #78977f;
        background: #4d303a;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-person strong,
    html[data-customer-theme="dark"] .ocean-profile .profile-orders-section h2,
    html[data-customer-theme="dark"] .ocean-profile .activity-head strong,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-heading h2,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-name,
    html[data-customer-theme="dark"] .ocean-profile .calendar-title strong {
        color: #edf6ef !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-photo,
    html[data-customer-theme="dark"] .ocean-profile .settings-btn {
        border-color: #78977f !important;
        background: #20382e !important;
        color: #edf6ef !important;
        box-shadow: var(--customer-shadow-control) #081810 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .member-pill {
        border-color: #ad6078;
        background: #3f2b33;
        color: #f2a0b6 !important;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-settings-panel {
        border-color: #78977f;
        background: #182d24;
        box-shadow: var(--customer-shadow-card) #081810;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-settings-panel > header strong,
    html[data-customer-theme="dark"] .ocean-profile .profile-preference-copy strong {
        color: #edf6ef;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-settings-panel > header button,
    html[data-customer-theme="dark"] .ocean-profile .profile-preference-icon {
        border-color: #78977f;
        background: #4d303a;
        color: #f08aa5;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-preference-card {
        border-color: #557863;
        background: #20382e;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-preference-copy small,
    html[data-customer-theme="dark"] .ocean-profile .profile-switch-choice b,
    html[data-customer-theme="dark"] .ocean-profile .activity-head span:last-child,
    html[data-customer-theme="dark"] .ocean-profile .metric-copy span,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-date,
    html[data-customer-theme="dark"] .ocean-profile .chart-month-labels,
    html[data-customer-theme="dark"] .ocean-profile .profile-line-chart .chart-y {
        color: #aec0b4 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-order-hub .order-link {
        border-color: #78977f;
        background: #3f2b33;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-order-hub .order-link:nth-child(2) {
        background: #263c30;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .ocean-profile .order-link small {
        color: #edf6ef;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-catalog-link { background: #263c30 !important; }
    html[data-customer-theme="dark"] .ocean-profile .activity-head {
        border-color: #78977f !important;
        background: #263c30;
    }

    html[data-customer-theme="dark"] .ocean-profile .activity-summary,
    html[data-customer-theme="dark"] .ocean-profile .chart-area {
        border-color: #78977f !important;
        background: #1b3027 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .metric-card {
        border-color: #668a70 !important;
        background: #34282d !important;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .ocean-profile .metric-card:nth-child(even) {
        background: #24382c !important;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .ocean-profile .chart-profile-total,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-count {
        border-color: #ad6078;
        background: #462f38;
        color: #f08aa5;
    }

    html[data-customer-theme="dark"] .ocean-profile .chart-line-plot {
        background: repeating-linear-gradient(to bottom, transparent 0, transparent 49px, rgba(174,192,180,.18) 50px, transparent 51px);
    }

    html[data-customer-theme="dark"] .ocean-profile .chart-line-point {
        border-color: #1b3027;
        background: #ed7697;
        box-shadow: 0 0 0 1.5px #ad6078;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-snack-heading {
        border-color: #668a70;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-snack-list li {
        border-color: #557863;
        background: #20382e;
    }

    html[data-customer-theme="dark"] .ocean-profile .calendar-card,
    html[data-customer-theme="dark"] .ocean-profile .calendar-grid,
    html[data-customer-theme="dark"] .ocean-profile .weekdays {
        color: #dce9df;
    }
</style>

<x-ocean-primary-navbar active="profile" />
