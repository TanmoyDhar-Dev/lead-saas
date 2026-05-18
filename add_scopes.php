<?php

$models = [
    'Lead', 'LeadSearch', 'Campaign',
    'EmailBodyTemplate', 'EmailSignatureTemplate', 'SenderIdentity', 'CsvImport'
];

foreach ($models as $model) {
    $path = __DIR__ . '/app/Models/' . $model . '.php';
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    if (strpos($content, 'use Illuminate\Database\Eloquent\Builder;') === false) {
        $content = str_replace('namespace App\Models;', "namespace App\Models;\n\nuse Illuminate\Database\Eloquent\Builder;\nuse App\Models\User;", $content);
    }
    
    if (strpos($content, 'public function scopeVisibleTo') === false) {
        $scope = "
    public function scopeVisibleTo(Builder \$query, User \$user): Builder
    {
        if (\$user->isAdmin()) {
            return \$query;
        }
        return \$query->where('user_id', \$user->id);
    }
";
        $content = preg_replace('/}\s*$/', $scope . "}\n", $content);
        file_put_contents($path, $content);
        echo "Updated $model\n";
    }
}

// CampaignRecipient
$crPath = __DIR__ . '/app/Models/CampaignRecipient.php';
if (file_exists($crPath)) {
    $crContent = file_get_contents($crPath);
    if (strpos($crContent, 'use Illuminate\Database\Eloquent\Builder;') === false) {
        $crContent = str_replace('namespace App\Models;', "namespace App\Models;\n\nuse Illuminate\Database\Eloquent\Builder;\nuse App\Models\User;", $crContent);
    }
    if (strpos($crContent, 'public function scopeVisibleTo') === false) {
        $scope = "
    public function scopeVisibleTo(Builder \$query, User \$user): Builder
    {
        if (\$user->isAdmin()) {
            return \$query;
        }
        return \$query->whereHas('campaign', function (\$q) use (\$user) {
            \$q->where('user_id', \$user->id);
        });
    }
";
        $crContent = preg_replace('/}\s*$/', $scope . "}\n", $crContent);
        file_put_contents($crPath, $crContent);
        echo "Updated CampaignRecipient\n";
    }
}

// LeadAutomationDetail
$ladPath = __DIR__ . '/app/Models/LeadAutomationDetail.php';
if (file_exists($ladPath)) {
    $ladContent = file_get_contents($ladPath);
    if (strpos($ladContent, 'use Illuminate\Database\Eloquent\Builder;') === false) {
        $ladContent = str_replace('namespace App\Models;', "namespace App\Models;\n\nuse Illuminate\Database\Eloquent\Builder;\nuse App\Models\User;", $ladContent);
    }
    if (strpos($ladContent, 'public function scopeVisibleTo') === false) {
        $scope = "
    public function scopeVisibleTo(Builder \$query, User \$user): Builder
    {
        if (\$user->isAdmin()) {
            return \$query;
        }
        return \$query->where(function (\$q) use (\$user) {
            \$q->whereHas('lead', function (\$q2) use (\$user) {
                \$q2->where('user_id', \$user->id);
            })->orWhereHas('campaign', function (\$q2) use (\$user) {
                \$q2->where('user_id', \$user->id);
            });
        });
    }
";
        $ladContent = preg_replace('/}\s*$/', $scope . "}\n", $ladContent);
        file_put_contents($ladPath, $ladContent);
        echo "Updated LeadAutomationDetail\n";
    }
}
