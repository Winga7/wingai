<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import Banner from "@/Components/Banner.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import DarkModeToggle from "@/Components/DarkModeToggle.vue";

defineProps({
  title: String,
});

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
  router.put(
    route("current-team.update"),
    {
      team_id: team.id,
    },
    {
      preserveState: false,
    }
  );
};

const logout = () => {
  router.post(route("logout"));
};
</script>

<template>
  <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <nav
      class="fixed top-0 right-0 left-0 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 z-50"
    >
      <!-- Primary Navigation Menu -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="shrink-0 flex items-center h-16">
              <Link :href="route('dashboard')">
                <ApplicationMark class="block w-auto" />
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
              <NavLink
                :href="route('dashboard')"
                :active="route().current('dashboard')"
              >
                Dashboard
              </NavLink>
              <NavLink
                :href="route('ask.index')"
                :active="route().current('ask.index')"
              >
                Chat IA
              </NavLink>
              <NavLink
                :href="route('ia.personalization.index')"
                :active="route().current('ia.personalization.index')"
              >
                Personnalisation IA
              </NavLink>
            </div>
          </div>

          <div class="hidden sm:flex sm:items-center sm:ms-6">
            <DarkModeToggle />
            <div class="ms-3 relative">
              <!-- Teams Dropdown -->
              <Dropdown
                v-if="$page.props.jetstream.hasTeamFeatures"
                align="right"
                width="60"
              >
                <template #trigger>
                  <span class="inline-flex rounded-md">
                    <button
                      type="button"
                      class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150"
                    >
                      {{ $page.props.auth.user.current_team.name }}

                      <svg
                        class="ms-2 -me-0.5 size-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"
                        />
                      </svg>
                    </button>
                  </span>
                </template>

                <template #content>
                  <div class="w-60">
                    <!-- Team Management -->
                    <div class="block px-4 py-2 text-xs text-gray-400">
                      Manage Team
                    </div>

                    <!-- Team Settings -->
                    <DropdownLink
                      :href="
                        route('teams.show', $page.props.auth.user.current_team)
                      "
                    >
                      Team Settings
                    </DropdownLink>

                    <DropdownLink
                      v-if="$page.props.jetstream.canCreateTeams"
                      :href="route('teams.create')"
                    >
                      Create New Team
                    </DropdownLink>

                    <!-- Team Switcher -->
                    <template v-if="$page.props.auth.user.all_teams.length > 1">
                      <div
                        class="border-t border-gray-200 dark:border-gray-600"
                      />

                      <div class="block px-4 py-2 text-xs text-gray-400">
                        Switch Teams
                      </div>

                      <template
                        v-for="team in $page.props.auth.user.all_teams"
                        :key="team.id"
                      >
                        <form @submit.prevent="switchToTeam(team)">
                          <DropdownLink as="button">
                            <div class="flex items-center">
                              <svg
                                v-if="
                                  team.id ==
                                  $page.props.auth.user.current_team_id
                                "
                                class="me-2 size-5 text-green-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                              >
                                <path
                                  stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                              </svg>

                              <div>{{ team.name }}</div>
                            </div>
                          </DropdownLink>
                        </form>
                      </template>
                    </template>
                  </div>
                </template>
              </Dropdown>
            </div>

            <!-- Settings Dropdown -->
            <div class="ms-3 relative">
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button
                    v-if="$page.props.jetstream.managesProfilePhotos"
                    class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition"
                  >
                    <img
                      class="size-8 rounded-full object-cover"
                      :src="$page.props.auth.user.profile_photo_url"
                      :alt="$page.props.auth.user.name"
                    />
                  </button>

                  <span v-else class="inline-flex rounded-md">
                    <button
                      type="button"
                      class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150"
                    >
                      {{ $page.props.auth.user.name }}

                      <svg
                        class="ms-2 -me-0.5 size-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                        />
                      </svg>
                    </button>
                  </span>
                </template>

                <template #content>
                  <!-- Account Management -->
                  <div class="block px-4 py-2 text-xs text-gray-400">
                    Gestion du Profil
                  </div>

                  <DropdownLink :href="route('profile.show')">
                    Profil
                  </DropdownLink>

                  <DropdownLink
                    v-if="$page.props.jetstream.hasApiFeatures"
                    :href="route('api-tokens.index')"
                  >
                    API Tokens
                  </DropdownLink>

                  <div class="border-t border-gray-200 dark:border-gray-600" />

                  <!-- Authentication -->
                  <form @submit.prevent="logout">
                    <DropdownLink as="button"> Déconnexion </DropdownLink>
                  </form>
                </template>
              </Dropdown>
            </div>
          </div>

          <!-- Hamburger -->
          <div class="flex items-center sm:hidden">
            <button
              @click="showingNavigationDropdown = !showingNavigationDropdown"
              class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out"
            >
              <svg
                class="h-6 w-6"
                stroke="currentColor"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path
                  :class="{
                    hidden: showingNavigationDropdown,
                    'inline-flex': !showingNavigationDropdown,
                  }"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
                <path
                  :class="{
                    hidden: !showingNavigationDropdown,
                    'inline-flex': showingNavigationDropdown,
                  }"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Responsive Navigation Menu -->
      <div
        :class="{
          'translate-x-0': showingNavigationDropdown,
          '-translate-x-full': !showingNavigationDropdown,
        }"
        class="fixed inset-0 z-40 transform sm:hidden bg-white dark:bg-gray-800 transition-transform duration-300 ease-in-out"
      >
        <div class="pt-5 pb-6 px-5">
          <div class="flex items-center justify-between">
            <div>
              <!-- Logo -->
            </div>
            <div class="-mr-2">
              <button
                @click="showingNavigationDropdown = false"
                class="rounded-md p-2 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
              >
                <span class="sr-only">Fermer le menu</span>
                <svg
                  class="h-6 w-6"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>
          <div class="mt-6">
            <nav class="grid gap-y-8">
              <ResponsiveNavLink
                :href="route('dashboard')"
                :active="route().current('dashboard')"
              >
                Dashboard
              </ResponsiveNavLink>
              <ResponsiveNavLink
                :href="route('ask.index')"
                :active="route().current('ask.index')"
              >
                Chat IA
              </ResponsiveNavLink>
              <ResponsiveNavLink
                :href="route('ia.personalization.index')"
                :active="route().current('ia.personalization.index')"
              >
                Personnalisation IA
              </ResponsiveNavLink>
            </nav>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Heading -->
    <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
      <div
        :class="[
          'max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 transition-all duration-300',
        ]"
      >
        <slot name="header" />
      </div>
    </header>

    <!-- Page Content -->
    <main class="pt-16">
      <slot />
    </main>
  </div>
</template>

<style scoped>
nav {
  z-index: 50;
}

main {
  min-height: calc(100vh - 65px);
}
</style>
