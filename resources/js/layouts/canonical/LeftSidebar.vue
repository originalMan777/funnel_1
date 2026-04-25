<template>
    <nav class="admin-sidebar" aria-label="Admin navigation">
        <section
            v-for="section in sections"
            :key="section.title"
            class="sidebar-section"
            :data-active="hasActiveLink(section)"
            :data-expandable="isExpandable(section)"
            :data-expanded="isExpanded(section.title)"
        >
            <button
                v-if="isExpandable(section)"
                type="button"
                class="sidebar-expand-zone"
                :aria-label="`${isExpanded(section.title) ? 'Collapse' : 'Expand'} ${section.title}`"
                :aria-expanded="isExpanded(section.title)"
                @click="toggleSection(section.title)"
            >
                <span
                    class="sidebar-section-caret"
                    :class="{ open: isExpanded(section.title) }"
                    aria-hidden="true"
                >
                    <svg viewBox="0 0 20 20" fill="none">
                        <path
                            d="M5.5 7.5 10 12l4.5-4.5"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                        />
                    </svg>
                </span>
            </button>

            <div class="sidebar-section-shell">
                <div class="sidebar-section-rail">
                    <span class="sidebar-section-title">{{ section.title }}</span>
                </div>

                <div class="sidebar-section-main">
                    <ul class="sidebar-link-group">
                        <li
                            v-for="link in visibleLinks(section)"
                            :key="`${section.title}-${link.name}`"
                        >
                            <Link
                                :href="link.route"
                                class="sidebar-link"
                                :class="{ active: isActive(link.route) }"
                                @click.stop
                            >
                                <span>{{ link.name }}</span>
                            </Link>
                        </li>
                    </ul>

                    <div
                        v-if="isExpandable(section)"
                        class="sidebar-hidden-links"
                        :class="{ open: isExpanded(section.title) }"
                    >
                        <ul class="sidebar-link-group sidebar-link-group-hidden">
                            <li
                                v-for="link in hiddenLinks(section)"
                                :key="`${section.title}-${link.name}`"
                            >
                                <Link
                                    :href="link.route"
                                    class="sidebar-link sidebar-link-sub"
                                    :class="{ active: isActive(link.route) }"
                                    @click.stop
                                >
                                    <span>{{ link.name }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div
                v-if="showBottomHint(section)"
                class="sidebar-collapsed-hint"
                aria-hidden="true"
            />
        </section>
    </nav>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

type SidebarLink = { name: string; route: string };
type SidebarSection = { title: string; links: SidebarLink[] };

const visibleCountFor = (section: SidebarSection) =>
    section.title === 'Communications' ? 4 : section.title === 'Analytics' ? 3 : 2;

const page = usePage();
const currentUrl = computed(() => String(page.url || ''));

const normalizePath = (url: string) => url.replace(/\/+$/, '');

const isActive = (target: string) => {
    const current = normalizePath(currentUrl.value);

    if (target.includes('?')) {
        return current === normalizePath(target);
    }

    const targetBase = normalizePath(target.split('?')[0]);

    if (targetBase === '/admin') {
        return current === '/admin';
    }

    return current === targetBase || current.startsWith(`${targetBase}/`);
};

const cs = (label: string) =>
    `/admin/coming-soon?m=${encodeURIComponent(label)}`;

const sections: SidebarSection[] = [
    {
        title: 'Dashboard',
        links: [
            { name: 'Overview', route: '/admin' },
            { name: 'Media Library', route: '/admin/media' },
        ],
    },
    {
        title: 'Tools',
        links: [
            { name: 'Generator', route: '/admin/content-formula' },
            { name: 'Post Importer', route: '/admin/post-importer' },
        ],
    },
    {
        title: 'Blog',
        links: [
            { name: 'All Posts', route: '/admin/posts' },
            { name: 'Create Post', route: '/admin/posts/create' },
            { name: 'Archived Posts', route: '/admin/posts/archived' },
            { name: 'Categories', route: '/admin/categories' },
            { name: 'Tags', route: '/admin/tags' },
        ],
    },
    {
        title: 'Leads',
        links: [
            { name: 'Lead Boxes', route: '/admin/lead-boxes' },
            { name: 'Lead Slots', route: '/admin/lead-slots' },
            { name: 'All Leads', route: '/admin/acquisition/contacts' },
            { name: 'All Popups', route: '/admin/popups' },
            { name: 'Create Popup', route: '/admin/popups/create' },
            { name: 'Popup Submissions', route: cs('Popup Submissions') },
            { name: 'Consultations', route: cs('Consultations') },
        ],
    },
    {
        title: 'Communications',
        links: [
            { name: 'Overview', route: '/admin/communications' },
            { name: 'Templates', route: '/admin/communications/templates' },
            { name: 'Email Composer', route: '/admin/communications/composer' },
            { name: 'Events', route: '/admin/communications/events' },
            { name: 'Deliveries', route: '/admin/communications/deliveries' },
            { name: 'Marketing Syncs', route: '/admin/communications/syncs' },
            { name: 'Settings', route: '/admin/communications/settings' },
            { name: 'Campaigns', route: '/admin/campaigns' },
            { name: 'Campaign Enrollments', route: '/admin/campaign-enrollments' },
        ],
    },
    {
        title: 'System',
        links: [{ name: 'Settings', route: cs('Settings') }],
    },
    {
        title: 'Analytics',
        links: [
            { name: 'Overview', route: '/admin/analytics' },
            { name: 'Metrics Catalog', route: '/admin/analytics/metrics' },
            { name: 'Funnels', route: '/admin/analytics/funnels' },
            { name: 'Scenarios', route: '/admin/analytics/scenarios' },
            { name: 'Pages', route: '/admin/analytics/pages' },
            { name: 'CTAs', route: '/admin/analytics/ctas' },
            { name: 'Lead Boxes', route: '/admin/analytics/lead-boxes' },
            { name: 'Popups', route: '/admin/analytics/popups' },
            { name: 'Conversions', route: '/admin/analytics/conversions' },
            { name: 'Attribution', route: '/admin/analytics/attribution' },
        ],
    },
];

const expanded = reactive<Record<string, boolean>>({});

const visibleLinks = (section: SidebarSection) => section.links.slice(0, visibleCountFor(section));
const hiddenLinks = (section: SidebarSection) => section.links.slice(visibleCountFor(section));
const isExpandable = (section: SidebarSection) => section.links.length > visibleCountFor(section);
const isExpanded = (title: string) => !!expanded[title];
const hasActiveLink = (section: SidebarSection) =>
    section.links.some((link) => isActive(link.route));
const showBottomHint = (section: SidebarSection) =>
    isExpandable(section) && !isExpanded(section.title);

const setSingleExpandedSection = (title: string | null) => {
    for (const section of sections) {
        expanded[section.title] = title !== null && section.title === title;
    }
};

const toggleSection = (title: string) => {
    if (isExpanded(title)) {
        setSingleExpandedSection(null);
        return;
    }

    setSingleExpandedSection(title);
};

watch(
    () => currentUrl.value,
    () => {
        const matchingSection = sections.find((section) =>
            hiddenLinks(section).some((link) => isActive(link.route)),
        );
        setSingleExpandedSection(matchingSection?.title ?? null);
    },
    { immediate: true },
);
</script>

<style scoped>
.admin-sidebar {
    width: 240px;
    height: 100vh;
    overflow-y: auto;
    padding: 0.75rem 0.75rem 0.9rem;
    color: white;
    background:
        radial-gradient(
            circle at top,
            rgba(255, 255, 255, 0.06),
            transparent 35%
        ),
        linear-gradient(180deg, #0f172a 0%, #111827 100%);
}

.sidebar-section {
    position: relative;
    margin-bottom: 0.5rem;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 1rem;
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.025)),
        rgba(15, 23, 42, 0.92);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
}

.sidebar-section[data-active='true'] {
    border-color: rgba(250, 204, 21, 0.2);
    background:
        linear-gradient(180deg, rgba(250, 204, 21, 0.06), rgba(255, 255, 255, 0.025)),
        rgba(15, 23, 42, 0.96);
}

.sidebar-section-shell {
    display: grid;
    grid-template-columns: 2rem minmax(0, 1fr);
    align-items: stretch;
    min-height: 100%;
}

.sidebar-section-rail {
    display: flex;
    align-items: center;
    justify-content: center;
    border-right: 1px solid rgba(255, 255, 255, 0.06);
    padding: 0.55rem 0.2rem;
    background: rgba(255, 255, 255, 0.025);
}

.sidebar-section-title {
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    font-size: 0.66rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #94a3b8;
}

.sidebar-section-main {
    position: relative;
    min-width: 0;
    padding: 0.45rem 2.35rem 0.45rem 0.45rem;
}

.sidebar-expand-zone {
    position: absolute;
    inset: 0 0 0 auto;
    z-index: 2;
    width: 2.35rem;
    border: 0;
    background: transparent;
    cursor: pointer;
    padding: 0;
}

.sidebar-section-caret {
    position: absolute;
    top: 0.62rem;
    right: 0.7rem;
    display: inline-flex;
    width: 0.95rem;
    height: 0.95rem;
    color: rgba(226, 232, 240, 0.92);
    transform: rotate(0deg);
    transition: transform 160ms ease;
    pointer-events: none;
}

.sidebar-section-caret.open {
    transform: rotate(180deg);
}

.sidebar-section-caret svg {
    width: 100%;
    height: 100%;
}

.sidebar-link-group {
    margin: 0;
    padding: 0;
    list-style: none;
}

.sidebar-link-group-hidden {
    padding-top: 0.18rem;
}

.sidebar-hidden-links {
    display: grid;
    grid-template-rows: 0fr;
    opacity: 0;
    transition:
        grid-template-rows 180ms ease,
        opacity 140ms ease;
}

.sidebar-hidden-links > .sidebar-link-group {
    overflow: hidden;
}

.sidebar-hidden-links.open {
    grid-template-rows: 1fr;
    opacity: 1;
}

.sidebar-link {
    position: relative;
    z-index: 3;
    display: flex;
    align-items: center;
    min-height: 2rem;
    padding: 0.42rem 0.65rem;
    border-radius: 0.75rem;
    color: #cbd5e1;
    text-decoration: none;
    font-size: 0.88rem;
    line-height: 1.2;
    transition:
        background-color 120ms ease,
        color 120ms ease;
}

.sidebar-link + .sidebar-link,
.sidebar-link-group li + li {
    margin-top: 0.18rem;
}

.sidebar-link:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
}

.sidebar-link.active {
    background: rgba(250, 204, 21, 0.09);
    color: #facc15;
    font-weight: 700;
}

.sidebar-link-sub {
    color: #bfd0e3;
}

.sidebar-collapsed-hint {
    position: absolute;
    right: 50%;
    bottom: 0.32rem;
    width: 46%;
    height: 1px;
    transform: translateX(50%);
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0),
        rgba(148, 163, 184, 0.28) 18%,
        rgba(226, 232, 240, 0.46) 50%,
        rgba(148, 163, 184, 0.28) 82%,
        rgba(255, 255, 255, 0)
    );
    opacity: 0.9;
    pointer-events: none;
    transition: opacity 140ms ease;
}
</style>
