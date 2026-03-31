<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    House,
    LayoutDashboard,
    Mail,
    Newspaper,
    Palette,
    PenSquare,
    Phone,
    ShieldCheck,
    Sparkles,
    SwatchBook,
    Tags,
    UserCircle2,
    Images,
    PanelsTopLeft,
} from 'lucide-vue-next';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { usePublicAuthNavigation } from '@/composables/usePublicAuthNavigation';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import FrontLayout from '@/layouts/FrontLayout.vue';
import admin from '@/routes/admin';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

const page = usePage<any>();
const { user, hasAdminWorkspace, isAdmin } = usePublicAuthNavigation();
const roleLabel = computed(() =>
    isAdmin.value ? 'Admin account' : 'User account',
);

const userLinks = computed(() => [
    {
        title: 'Profile settings',
        description: 'Update your name, email, and account basics.',
        href: editProfile(),
        icon: UserCircle2,
    },
    {
        title: 'Security',
        description: 'Manage password and account protection.',
        href: editSecurity(),
        icon: ShieldCheck,
    },
    {
        title: 'Appearance',
        description: 'Adjust the way your account area looks.',
        href: editAppearance(),
        icon: Palette,
    },
    {
        title: 'Home page',
        description: 'Go back to the main Nojo Consult website.',
        href: '/',
        icon: House,
    },
    {
        title: 'Consultation',
        description: 'Jump into the consultation area of the site.',
        href: '/consultation',
        icon: Phone,
    },
    {
        title: 'Resources & blog',
        description: 'Browse resources, articles, and site content.',
        href: '/resources',
        icon: Newspaper,
    },
]);

const adminLinks = computed(() => [
    {
        title: 'Admin dashboard',
        description: 'Open the main admin workspace.',
        href: admin.index(),
        icon: LayoutDashboard,
    },
    {
        title: 'Content Formula',
        description: 'Open the content generation system.',
        href: admin.contentFormula.index(),
        icon: Sparkles,
    },
    {
        title: 'Posts',
        description: 'Manage blog posts and editorial content.',
        href: admin.posts.index(),
        icon: PenSquare,
    },
    {
        title: 'Categories',
        description: 'Manage content categories for the blog.',
        href: admin.categories.index(),
        icon: SwatchBook,
    },
    {
        title: 'Tags',
        description: 'Manage tagging and content grouping.',
        href: admin.tags.index(),
        icon: Tags,
    },
    {
        title: 'Media library',
        description: 'Manage uploaded images and media assets.',
        href: admin.media.index(),
        icon: Images,
    },
    {
        title: 'Lead boxes',
        description: 'Control lead capture blocks and placements.',
        href: admin.leadBoxes.index(),
        icon: PanelsTopLeft,
    },
    {
        title: 'Popups',
        description: 'Manage popup funnels and lead capture prompts.',
        href: admin.popups.index(),
        icon: Mail,
    },
]);
</script>

<template>
    <FrontLayout>
        <Head title="Profile" />

        <h1 class="sr-only">Profile</h1>

        <div class="mx-auto flex max-w-5xl flex-col space-y-8">
            <div
                class="rounded-3xl border border-black/5 bg-white/90 p-2 shadow-[0_24px_60px_rgba(15,23,42,0.08)]"
            >
                <div
                    class="overflow-hidden rounded-3xl border border-border/70 bg-card shadow-sm"
                >
                    <div
                        class="border-b border-border/70 bg-muted/40 px-6 py-5"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.2em] text-muted-foreground uppercase"
                        >
                            Welcome back
                        </p>
                        <div
                            class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                        >
                            <div>
                                <h2
                                    class="text-2xl font-semibold tracking-tight text-foreground"
                                >
                                    {{ user.name }}
                                </h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    This is your account home after login. Use
                                    the links below to move through your user
                                    tools, and if you are an admin, your admin
                                    controls will appear separately.
                                </p>
                            </div>

                            <div
                                class="inline-flex w-fit items-center rounded-full border border-border bg-background px-3 py-1.5 text-sm font-medium text-foreground"
                            >
                                {{ roleLabel }}
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid gap-0 border-t border-border/70 md:grid-cols-[1.2fr_0.8fr] md:border-t-0"
                    >
                        <div class="px-6 py-5 md:border-r md:border-border/70">
                            <p
                                class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                            >
                                Account snapshot
                            </p>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="text-muted-foreground">Name</dt>
                                    <dd
                                        class="text-right font-medium text-foreground"
                                    >
                                        {{ user.name }}
                                    </dd>
                                </div>
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="text-muted-foreground">Email</dt>
                                    <dd
                                        class="text-right font-medium text-foreground"
                                    >
                                        {{ user.email }}
                                    </dd>
                                </div>
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="text-muted-foreground">
                                        Email status
                                    </dt>
                                    <dd
                                        class="text-right font-medium text-foreground"
                                    >
                                        {{
                                            user.email_verified_at
                                                ? 'Verified'
                                                : 'Unverified'
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="px-6 py-5">
                            <p
                                class="text-xs font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                            >
                                What this page is for
                            </p>
                            <p
                                class="mt-4 text-sm leading-6 text-muted-foreground"
                            >
                                This profile page acts as your post-login hub.
                                User links stay separate from admin links so the
                                account side and the management side do not feel
                                mixed together.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <Heading
                        variant="small"
                        title="User links"
                        description="These links are for the normal account side of the site."
                    />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <Link
                        v-for="item in userLinks"
                        :key="
                            typeof item.href === 'string'
                                ? item.href
                                : item.href.url
                        "
                        :href="item.href"
                        class="group rounded-2xl border border-border/70 bg-card p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-2xl border border-border/70 bg-muted/50 p-2.5 text-muted-foreground transition group-hover:text-foreground"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ item.title }}
                                </h3>
                                <p
                                    class="mt-1 text-sm leading-6 text-muted-foreground"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <div v-if="hasAdminWorkspace" class="space-y-4">
                <div>
                    <Heading
                        variant="small"
                        title="Admin links"
                        description="These links are only for admin users and stay separated from the normal user side."
                    />
                </div>

                <div
                    class="overflow-hidden rounded-3xl border border-primary/15 bg-primary/5 p-5 shadow-sm"
                >
                    <div
                        class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold tracking-[0.18em] text-primary/80 uppercase"
                            >
                                Admin workspace
                            </p>
                            <p
                                class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground"
                            >
                                Your admin tools are available from this hub
                                first, but they remain protected behind the
                                existing admin routes and middleware.
                            </p>
                        </div>

                        <div
                            class="inline-flex w-fit items-center rounded-full border border-primary/20 bg-background px-3 py-1.5 text-sm font-medium text-foreground"
                        >
                            Admin access enabled
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <Link
                            v-for="item in adminLinks"
                            :key="
                                typeof item.href === 'string'
                                    ? item.href
                                    : item.href.url
                            "
                            :href="item.href"
                            class="group rounded-3xl border border-primary/15 bg-background p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-md"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="rounded-2xl border border-primary/10 bg-primary/10 p-3 text-primary transition group-hover:scale-[1.02] group-hover:text-primary"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-6 w-6"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3
                                        class="text-base font-semibold text-foreground"
                                    >
                                        {{ item.title }}
                                    </h3>
                                    <p
                                        class="mt-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <div
                class="rounded-3xl border border-border/70 bg-card p-6 shadow-sm"
            >
                <div class="flex flex-col space-y-6">
                    <Heading
                        variant="small"
                        title="Profile information"
                        description="Update your name and email address."
                    />

                    <Form
                        v-bind="ProfileController.update.form()"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                class="mt-1 block w-full"
                                name="name"
                                :default-value="user.name"
                                required
                                autocomplete="name"
                                placeholder="Full name"
                            />
                            <InputError class="mt-2" :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                name="email"
                                :default-value="user.email"
                                required
                                autocomplete="username"
                                placeholder="Email address"
                            />
                            <InputError class="mt-2" :message="errors.email" />
                        </div>

                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="-mt-4 text-sm text-muted-foreground">
                                Your email address is unverified.
                                <Link
                                    :href="send()"
                                    as="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current dark:decoration-neutral-500"
                                >
                                    Click here to resend the verification email.
                                </Link>
                            </p>

                            <div
                                v-if="status === 'verification-link-sent'"
                                class="mt-2 text-sm font-medium text-green-600"
                            >
                                A new verification link has been sent to your
                                email address.
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button
                                :disabled="processing"
                                data-test="update-profile-button"
                            >
                                Save
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-neutral-600"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </div>
            </div>

            <div
                class="rounded-3xl border border-border/70 bg-card p-6 shadow-sm"
            >
                <DeleteUser />
            </div>
        </div>
    </FrontLayout>
</template>
