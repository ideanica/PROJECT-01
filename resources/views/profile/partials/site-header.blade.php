<style>
    html[data-customer-theme="dark"] body.ocean-profile {
        --profile-ink: #d6e9ed;
        --profile-outline: #684f48;
        --profile-pink: #684f48;
        --profile-pink-strong: #ccd6d6;
        --profile-mint: #684f48;
        --profile-paper: #684f48;
        --profile-muted: #684f48;
        color-scheme: dark;
        background:
            linear-gradient(rgba(104, 79, 72, .055) 1px, transparent 1px),
            linear-gradient(90deg, rgba(104, 79, 72, .055) 1px, transparent 1px),
            #684f48;
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
        border-color: #684f48;
        background: #684f48;
        box-shadow: var(--customer-shadow-control) #684f48;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-browserbar {
        border-bottom-color: #684f48;
        background: #684f48;
        color: #d6e9ed;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-navrow {
        background: #684f48;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-nav a,
    html[data-customer-theme="dark"] .profile-site-header .op-nav a i {
        color: #ccd6d6;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-nav a:hover,
    html[data-customer-theme="dark"] .profile-site-header .op-nav a.is-active,
    html[data-customer-theme="dark"] .profile-site-header .op-nav a.is-active i {
        color: #ccd6d6;
    }

    html[data-customer-theme="dark"] .profile-site-header .op-nav a.is-active::after {
        background: #ccd6d6;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-main,
    html[data-customer-theme="dark"] .ocean-profile .section-card,
    html[data-customer-theme="dark"] .ocean-profile .calendar-card,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-card {
        border-color: #684f48 !important;
        background: #684f48 !important;
        color: #d6e9ed !important;
        box-shadow: var(--customer-shadow-card) #684f48 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-top {
        border-color: #684f48;
        background: #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-person strong,
    html[data-customer-theme="dark"] .ocean-profile .profile-orders-section h2,
    html[data-customer-theme="dark"] .ocean-profile .activity-head strong,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-heading h2,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-name,
    html[data-customer-theme="dark"] .ocean-profile .calendar-title strong {
        color: #d6e9ed !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-photo,
    html[data-customer-theme="dark"] .ocean-profile .settings-btn {
        border-color: #684f48 !important;
        background: #684f48 !important;
        color: #d6e9ed !important;
        box-shadow: var(--customer-shadow-control) #684f48 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .member-pill {
        border-color: #684f48;
        background: #684f48;
        color: #ccd6d6 !important;
        box-shadow: var(--customer-shadow-control) #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-settings-panel {
        border-color: #684f48;
        background: #684f48;
        box-shadow: var(--customer-shadow-card) #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-settings-panel > header strong,
    html[data-customer-theme="dark"] .ocean-profile .profile-preference-copy strong {
        color: #d6e9ed;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-settings-panel > header button,
    html[data-customer-theme="dark"] .ocean-profile .profile-preference-icon {
        border-color: #684f48;
        background: #684f48;
        color: #ccd6d6;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-preference-card {
        border-color: #684f48;
        background: #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-preference-copy small,
    html[data-customer-theme="dark"] .ocean-profile .profile-switch-choice b,
    html[data-customer-theme="dark"] .ocean-profile .activity-head span:last-child,
    html[data-customer-theme="dark"] .ocean-profile .metric-copy span,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-date,
    html[data-customer-theme="dark"] .ocean-profile .chart-month-labels,
    html[data-customer-theme="dark"] .ocean-profile .profile-line-chart .chart-y {
        color: #684f48 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-order-hub .order-link {
        border-color: #684f48;
        background: #684f48;
        box-shadow: var(--customer-shadow-control) #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-order-hub .order-link:nth-child(2) {
        background: #684f48;
        box-shadow: var(--customer-shadow-control) #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .order-link small {
        color: #d6e9ed;
    }

    html[data-customer-theme="dark"] .ocean-profile .activity-head {
        border-color: #684f48 !important;
        background: #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .activity-summary,
    html[data-customer-theme="dark"] .ocean-profile .chart-area {
        border-color: #684f48 !important;
        background: #684f48 !important;
    }

    html[data-customer-theme="dark"] .ocean-profile .metric-card {
        border-color: #684f48 !important;
        background: #684f48 !important;
        box-shadow: var(--customer-shadow-control) #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .metric-card:nth-child(even) {
        background: #684f48 !important;
        box-shadow: var(--customer-shadow-control) #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .chart-profile-total,
    html[data-customer-theme="dark"] .ocean-profile .profile-snack-count {
        border-color: #684f48;
        background: #684f48;
        color: #ccd6d6;
    }

    html[data-customer-theme="dark"] .ocean-profile .chart-line-plot {
        background: repeating-linear-gradient(to bottom, transparent 0, transparent 49px, rgba(104, 79, 72, .18) 50px, transparent 51px);
    }

    html[data-customer-theme="dark"] .ocean-profile .chart-line-point {
        border-color: #684f48;
        background: #684f48;
        box-shadow: 0 0 0 1.5px #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-snack-heading {
        border-color: #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .profile-snack-list li {
        border-color: #684f48;
        background: #684f48;
    }

    html[data-customer-theme="dark"] .ocean-profile .calendar-card,
    html[data-customer-theme="dark"] .ocean-profile .calendar-grid,
    html[data-customer-theme="dark"] .ocean-profile .weekdays {
        color: #d6e9ed;
    }
</style>

<x-ocean-primary-navbar active="profile" />
