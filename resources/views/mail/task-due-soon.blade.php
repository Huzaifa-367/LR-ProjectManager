A task deadline is approaching in {{ config('app.name') }}.

Task: {{ $taskTitle }}
@if ($projectName)
Project: {{ $projectName }}
@endif
@if ($deadlineDate)
Due date: {{ $deadlineDate }}
@endif

View task: {{ $actionUrl }}
