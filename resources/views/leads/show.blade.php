<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lead Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('leads.index') }}" class="text-indigo-600 hover:text-indigo-900">&larr; Back to Leads</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Person Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><strong class="text-gray-700">Name:</strong> {{ $lead->person_name ?: '-' }}</div>
                        <div><strong class="text-gray-700">Email:</strong> {{ $lead->personal_email_address ?: '-' }}</div>
                        <div><strong class="text-gray-700">LinkedIn URL:</strong> 
                            @if($lead->personal__linkdin_url)
                                <a href="{{ $lead->personal__linkdin_url }}" target="_blank" class="text-blue-600 hover:underline">View Profile</a>
                            @else
                                -
                            @endif
                        </div>
                        <div><strong class="text-gray-700">Location:</strong> {{ $lead->personal_address_with_country ?: '-' }}</div>
                        <div class="md:col-span-2"><strong class="text-gray-700">LinkedIn Bio:</strong> <p class="text-sm text-gray-600">{{ $lead->personal_linkdin_bio ?: '-' }}</p></div>
                        <div class="md:col-span-2"><strong class="text-gray-700">Profile About:</strong> <p class="text-sm text-gray-600">{{ $lead->personal_profile_about ?: '-' }}</p></div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Company Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><strong class="text-gray-700">Company Name:</strong> {{ $lead->company_name ?: '-' }}</div>
                        <div><strong class="text-gray-700">Website:</strong> 
                            @if($lead->company_website)
                                <a href="{{ Str::startsWith($lead->company_website, ['http://', 'https://']) ? $lead->company_website : 'https://' . $lead->company_website }}" target="_blank" class="text-blue-600 hover:underline">{{ $lead->company_website }}</a>
                            @else
                                -
                            @endif
                        </div>
                        <div><strong class="text-gray-700">LinkedIn URL:</strong>
                            @if($lead->company_linkdin_url)
                                <a href="{{ $lead->company_linkdin_url }}" target="_blank" class="text-blue-600 hover:underline">View Company</a>
                            @else
                                -
                            @endif
                        </div>
                        <div><strong class="text-gray-700">Address:</strong> {{ $lead->company_address ?: '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Search Metadata</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div><strong class="text-gray-700">Main Search Query:</strong> {{ $lead->main_search_query ?: '-' }}</div>
                        <div><strong class="text-gray-700">Country (Search Param):</strong> {{ $lead->country_by_search_param ?: '-' }}</div>
                        <div><strong class="text-gray-700">City (Search Param):</strong> {{ $lead->city_by_search_param ?: '-' }}</div>
                        <div><strong class="text-gray-700">Industry (Search Param):</strong> {{ $lead->industry_by_search_param ?: '-' }}</div>
                        <div><strong class="text-gray-700">Industry (Apify):</strong> {{ $lead->industry_by_apifyapi ?: '-' }}</div>
                        <div><strong class="text-gray-700">Position (Search Param):</strong> {{ $lead->position_by_search_param ?: '-' }}</div>
                        <div><strong class="text-gray-700">Position (Apify):</strong> {{ $lead->position_by_apifiapi ?: '-' }}</div>
                        <div><strong class="text-gray-700">Source:</strong> {{ $lead->source ?: '-' }}</div>
                        <div><strong class="text-gray-700">Email Sent Status:</strong> <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ $lead->email_sent }}</span></div>
                        <div><strong class="text-gray-700">Imported At:</strong> {{ $lead->imported_at ? $lead->imported_at->format('Y-m-d H:i') : '-' }}</div>
                        <div><strong class="text-gray-700">Created At:</strong> {{ $lead->created_at->format('Y-m-d H:i') }}</div>
                        <div><strong class="text-gray-700">Updated At:</strong> {{ $lead->updated_at->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Automation History</h3>
                    @if($lead->leadAutomationDetails && $lead->leadAutomationDetails->count() > 0)
                        <div class="space-y-6">
                            @foreach($lead->leadAutomationDetails as $detail)
                                <div class="border rounded-md p-4 bg-gray-50">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm mb-4">
                                        <div><strong class="text-gray-700">Created At:</strong> {{ $detail->created_at->format('Y-m-d H:i') }}</div>
                                        <div><strong class="text-gray-700">Email Status:</strong> {{ $detail->email_sent ?: 'Pending' }}</div>
                                        <div class="md:col-span-2"><strong class="text-gray-700">Email Topic:</strong> {{ $detail->email_topic ?: '-' }}</div>
                                        <div class="md:col-span-2"><strong class="text-gray-700">Topic Source:</strong> {{ $detail->topic_source ?: '-' }}</div>
                                        <div><strong class="text-gray-700">Search Window:</strong> {{ $detail->search_window ?: '-' }}</div>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        @if($detail->website_summary)
                                            <div><strong class="text-gray-700 block">Website Summary:</strong> <p class="text-gray-600">{{ $detail->website_summary }}</p></div>
                                        @endif
                                        @if($detail->news_summary)
                                            <div><strong class="text-gray-700 block">News Summary:</strong> <p class="text-gray-600">{{ $detail->news_summary }}</p></div>
                                        @endif
                                        @if($detail->product_summary)
                                            <div><strong class="text-gray-700 block">Product Summary:</strong> <p class="text-gray-600">{{ $detail->product_summary }}</p></div>
                                        @endif
                                        @if($detail->growth_summary)
                                            <div><strong class="text-gray-700 block">Growth Summary:</strong> <p class="text-gray-600">{{ $detail->growth_summary }}</p></div>
                                        @endif
                                        @if($detail->linkedin_summary)
                                            <div><strong class="text-gray-700 block">LinkedIn Summary:</strong> <p class="text-gray-600">{{ $detail->linkedin_summary }}</p></div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 italic">No automation history yet.</p>
                    @endif
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>
