<?php

namespace Modules\Agenda\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Agenda\Entities\Agenda;
use Modules\Agenda\Http\Resources\AgendaResource;

class AgendaController extends Controller
{
    function index() {
        $agendas = Agenda::query()
            ->where('user_id', auth()->user()->id)
            ->with([])
            ->latest()
            ->get();
        return AgendaResource::collection($agendas);
    }

    function store(Request $request) {
        $request->validate([
            'title' => 'required|min:2|max:250',
            'description' => 'required',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s',
        ]);
        $agenda = Agenda::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'user_id' => $request->user_id ?? auth()->user()->id,
            'created_by' => auth()->user()->id
        ]);

        return apiResponse(
            data: $agenda,
            message: 'Agenda Created Successfully'
        );
    }
}
