<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale, t } = useI18n()

const currentLocale = ref(locale.value)

function toggleLanguage() {
  locale.value = locale.value === 'en' ? 'pt-BR' : 'en'
  currentLocale.value = locale.value
}
</script>

<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 font-sans p-6">
    <header class="max-w-7xl mx-auto flex items-center justify-between pb-6 border-b border-slate-800">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-bold text-xl shadow-lg shadow-indigo-500/30">
          R
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white">{{ t('calendars.title') }}</h1>
          <p class="text-xs text-slate-400">Open-Source Calendar Mash-up Platform</p>
        </div>
      </div>

      <button 
        @click="toggleLanguage" 
        class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 transition"
      >
        🌐 {{ currentLocale.toUpperCase() }}
      </button>
    </header>

    <main class="max-w-7xl mx-auto mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
      <!-- Sidebar / Calendar Sources -->
      <aside class="md:col-span-1 bg-slate-800/60 backdrop-blur-md p-4 rounded-xl border border-slate-700/50">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">
          {{ t('calendars.mailbox.ingest_title') }}
        </h2>
        <div class="space-y-3">
          <div class="p-3 bg-slate-900/80 rounded-lg border border-slate-700/50 flex items-center justify-between text-xs">
            <span class="font-medium text-indigo-400">{{ t('calendars.sources.email_invite') }}</span>
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          </div>
          <div class="p-3 bg-slate-900/80 rounded-lg border border-slate-700/50 flex items-center justify-between text-xs">
            <span class="font-medium text-purple-400">{{ t('calendars.sources.ics_feed_subscription') }}</span>
            <span class="text-slate-500">Idle</span>
          </div>
        </div>
      </aside>

      <!-- Calendar Main View Area -->
      <section class="md:col-span-3 bg-slate-800/60 backdrop-blur-md p-6 rounded-xl border border-slate-700/50">
        <div class="flex items-center justify-between mb-6">
          <div class="flex space-x-2">
            <button class="px-3 py-1.5 text-xs font-medium rounded-md bg-indigo-600 text-white">{{ t('calendars.views.month') }}</button>
            <button class="px-3 py-1.5 text-xs font-medium rounded-md bg-slate-800 text-slate-300 hover:bg-slate-700">{{ t('calendars.views.week') }}</button>
            <button class="px-3 py-1.5 text-xs font-medium rounded-md bg-slate-800 text-slate-300 hover:bg-slate-700">{{ t('calendars.views.day') }}</button>
            <button class="px-3 py-1.5 text-xs font-medium rounded-md bg-slate-800 text-slate-300 hover:bg-slate-700">{{ t('calendars.views.agenda') }}</button>
          </div>
        </div>

        <div class="h-96 border border-dashed border-slate-700 rounded-lg flex items-center justify-center text-slate-500 text-sm">
          📅 Rendezvous Calendar Grid (1200px measure)
        </div>
      </section>
    </main>
  </div>
</template>
