## HOW TO USE

This is a package to initialize Nayra BPMN engine into a Laravel Project.

First create a Laravel application.

Configure a database
Configure the Queue service

## Add to composer.json
Add this repository to composer.json
```
    "repositories": [
        {
          "type": "vcs",
          "url": "https://github.com/caleeli/laravel-nayra.git"
        }
    ],
```
Then run:
```
composer require processmaker/laravel-nayra
```

## BPMN example

You could find a BPMN example in this project [./bpmp/example.bpmn]

Copy this BPMN to your Laravel project.

## Example of web routes

```
<?php

use Illuminate\Support\Facades\Route;
use ProcessMaker\Laravel\Facades\Nayra;
use ProcessMaker\Laravel\Models\Request;

Route::get('/start', function() {
    $request = Nayra::startProcess(base_path('bpmn/example.bpmn'), 'node_1');
    return redirect('/status/' . $request->getId());
});

Route::get('/status/{requestId}', function($requestId) {
    $instance = Nayra::getInstanceById($requestId);
    $request = Request::find($requestId);
    return view('request', compact('request', 'instance'));
});

Route::get('/complete/{requestId}/{tokenId}', function($requestId, $tokenId) {
    $request = Nayra::completeTask($requestId, $tokenId);
    return redirect('/status/' . $request->getId());
});

```

## A blade template to follow the process
Create a blade template [resources/views/request.blade.php]

```
<pre>
[<a href="/start">Start a New Request</a>]
<u>Request Id</u>: {{ $instance->getId() }}
<u>Status</u>: {{ $request->status }}
<u>Active Tasks</u>:
@foreach($instance->getTokens() as $token)
 - {{ $token->getOwnerElement()->getName() }} @if(in_array($token->getOwnerElement()->getBpmnElement()->localName, ['task', 'userTask'])) [<a href="/complete/{{ $instance->getId() }}/{{ $token->getId() }}">complete</a>]@endif

@endforeach
<u>Data</u>:
@json((object) $instance->getDataStore()->getData(), JSON_PRETTY_PRINT)
</pre>
<script>
setInterval('location.reload()', 5000);
</script>
```
