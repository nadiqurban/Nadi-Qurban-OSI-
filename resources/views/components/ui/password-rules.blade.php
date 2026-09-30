{{-- Rule checklist (Login.dc.html): needs an Alpine passwordStrength() scope. --}}
<div class="mt-4 flex flex-col gap-2 rounded-[9px] bg-bg p-[14px]">
    <template x-for="rule in rules" :key="rule.label">
        <div class="flex items-center gap-2 text-[12.5px]" :class="rule.ok ? 'text-success' : 'text-faint'">
            <i class="text-[15px]" :class="rule.ok ? 'ph-fill ph-check-circle' : 'ph ph-circle'"></i>
            <span x-text="rule.label"></span>
        </div>
    </template>
</div>
