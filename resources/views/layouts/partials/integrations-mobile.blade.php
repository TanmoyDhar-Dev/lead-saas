<div class="lg:hidden">
    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 px-3">Integrations</p>
    <template x-for="item in $store.integrations.items" :key="item.id">
        <button
            type="button"
            @click="$store.integrations.openModal(item.id); sidebarOpen = false"
            class="flex items-center w-full px-3 py-2.5 text-sm font-medium rounded-xl transition-all mb-1"
            :class="item.connected ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-400 hover:text-white hover:bg-navy-800'"
        >
            <span class="w-8 h-8 rounded-lg bg-[#0078D4]/20 flex items-center justify-center mr-3 shrink-0" x-html="item.icon"></span>
            <span class="flex-1 text-left">
                <span x-text="item.label"></span>
                <span class="block text-[10px] opacity-70" x-text="item.connected ? 'Connected' : 'Tap to connect'"></span>
            </span>
        </button>
    </template>
</div>
