<x-app-layout>
    <x-slot name="header">
        Profile Settings
    </x-slot>

    <x-slot name="subheader">
        Manage your account information and security
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
