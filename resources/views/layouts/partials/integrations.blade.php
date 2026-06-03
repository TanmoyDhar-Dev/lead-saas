<div class="hidden lg:block">
    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 transition-all duration-300 overflow-hidden whitespace-nowrap"
       :class="sidebarCollapsed ? 'px-0 text-center' : 'px-3'">
        <span :class="sidebarCollapsed ? 'hidden' : ''">Integrations</span>
        <span :class="sidebarCollapsed ? '' : 'hidden'" class="text-slate-600">•••</span>
    </p>
    <div class="space-y-2" :class="sidebarCollapsed ? 'px-1' : 'px-0'">
        <template x-for="item in $store.integrations.items" :key="item.id">
            <div class="rounded-xl border border-navy-800 bg-navy-950/50 p-2.5 transition-all"
                 :class="sidebarCollapsed ? 'text-center' : ''">
                <div class="flex items-center gap-2" :class="sidebarCollapsed ? 'justify-center' : ''">
                    <div class="w-8 h-8 rounded-lg bg-[#0078D4]/20 flex items-center justify-center shrink-0" x-html="item.icon"></div>
                    <div class="min-w-0 flex-1" x-show="!sidebarCollapsed">
                        <p class="text-xs font-semibold text-slate-200 truncate" x-text="item.label"></p>
                        <p class="text-[10px] text-slate-500 truncate" x-text="item.connected ? (item.email || 'Connected') : 'Not connected'"></p>
                    </div>
                </div>
                <button
                    type="button"
                    x-show="!sidebarCollapsed"
                    @click="$store.integrations.openModal(item.id)"
                    class="mt-2 w-full text-xs font-semibold py-1.5 rounded-lg transition-all"
                    :class="item.connected
                        ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/25'
                        : 'bg-brand-blue/20 text-blue-300 border border-brand-blue/30 hover:bg-brand-blue/30'"
                    x-text="item.connected ? 'Connected' : 'Connect'"
                ></button>
            </div>
        </template>
    </div>
</div>
