You were assigned to a task in {{ config('app.name') }}.

Task: {{ $taskTitle }}
@if ($projectName)
Project: {{ $projectName }}
@endif
@if ($assignedByName)
Assigned by: {{ $assignedByName }}
@endif

View task: {{ $actionUrl }}
