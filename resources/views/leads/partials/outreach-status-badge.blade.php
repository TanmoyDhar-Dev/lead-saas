@php
    $outreachStatus = $status ?? $lead->campaignRecipients->first()?->status;
@endphp

@if($outreachStatus === 'sent')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-50 text-emerald-600">Sent</span>
@elseif($outreachStatus === 'drafted')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-600">Drafted</span>
@elseif($outreachStatus === 'failed')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-50 text-red-600">Failed</span>
@elseif(in_array($outreachStatus, ['queued', 'pending'], true))
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-50 text-blue-600 animate-pulse">
        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span>
        Queued
    </span>
@else
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-500">---</span>
@endif
