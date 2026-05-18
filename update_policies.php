<?php

$models = [
    'Lead', 'LeadSearch', 'Campaign',
    'EmailBodyTemplate', 'EmailSignatureTemplate', 'SenderIdentity', 'CsvImport'
];

foreach ($models as $model) {
    $policyPath = __DIR__ . '/app/Policies/' . $model . 'Policy.php';
    $var = lcfirst($model);
    $content = "<?php\n\nnamespace App\Policies;\n\nuse App\Models\\$model;\nuse App\Models\User;\nuse Illuminate\Auth\Access\HandlesAuthorization;\n\nclass {$model}Policy\n{\n    use HandlesAuthorization;\n\n    public function before(User \$user, \$ability)\n    {\n        if (\$user->isAdmin()) {\n            return true;\n        }\n    }\n\n    public function viewAny(User \$user)\n    {\n        return true;\n    }\n\n    public function view(User \$user, $model \$$var)\n    {\n        return \$user->id === \${$var}->user_id;\n    }\n\n    public function create(User \$user)\n    {\n        return true;\n    }\n\n    public function update(User \$user, $model \$$var)\n    {\n        return \$user->id === \${$var}->user_id;\n    }\n\n    public function delete(User \$user, $model \$$var)\n    {\n        return \$user->id === \${$var}->user_id;\n    }\n}\n";
    file_put_contents($policyPath, $content);
}

$ladPath = __DIR__ . '/app/Policies/LeadAutomationDetailPolicy.php';
$ladContent = "<?php\n\nnamespace App\Policies;\n\nuse App\Models\LeadAutomationDetail;\nuse App\Models\User;\nuse Illuminate\Auth\Access\HandlesAuthorization;\n\nclass LeadAutomationDetailPolicy\n{\n    use HandlesAuthorization;\n\n    public function before(User \$user, \$ability)\n    {\n        if (\$user->isAdmin()) {\n            return true;\n        }\n    }\n\n    public function viewAny(User \$user)\n    {\n        return true;\n    }\n\n    public function view(User \$user, LeadAutomationDetail \$detail)\n    {\n        \$lead = \$detail->lead;\n        \$campaign = \$detail->campaign;\n        return (\$lead && \$lead->user_id === \$user->id) || (\$campaign && \$campaign->user_id === \$user->id);\n    }\n\n    public function create(User \$user)\n    {\n        return true;\n    }\n\n    public function update(User \$user, LeadAutomationDetail \$detail)\n    {\n        return \$this->view(\$user, \$detail);\n    }\n\n    public function delete(User \$user, LeadAutomationDetail \$detail)\n    {\n        return \$this->view(\$user, \$detail);\n    }\n}\n";
file_put_contents($ladPath, $ladContent);
