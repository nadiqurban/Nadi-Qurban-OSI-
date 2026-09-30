{{--
    Strength meter + rules (Login.dc.html reset view). Must be inside an Alpine scope
    created with x-data="passwordStrength()" whose `pw` is bound to the password input.
--}}
<div>
    <div class="mt-[10px] flex gap-[5px]" aria-hidden="true">
        <template x-for="k in [0, 1, 2, 3]" :key="k">
            <span class="h-1.5 flex-1 rounded-[20px]" :style="`background: ${k < score && pw.length ? color : '#E2E8F0'}`"></span>
        </template>
    </div>
    <div class="mt-1.5 text-[11.5px] text-faint">Kekuatan: <span class="font-bold" :style="`color: ${color}`" x-text="label"></span></div>
</div>
