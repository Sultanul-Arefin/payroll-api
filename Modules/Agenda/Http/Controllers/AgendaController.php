<?php

namespace Modules\Agenda\Http\Controllers;

use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Agenda\Entities\Agenda;
use Modules\Agenda\Http\Resources\AgendaResource;
use Modules\Agenda\Http\Resources\CalendarAgendaResource;

class AgendaController extends Controller
{
    public function index()
    {
        $agendas = Agenda::query()
            ->whereHas(
                'user', function(Builder $builder){
                    $builder->where('company_id', auth()->user()->company_id);
                }
            )
            ->with([])
            ->latest()
            ->get();

        return AgendaResource::collection($agendas);
    }

    public function calendar_agenda()
    {
        $agendas = Agenda::query()
            ->where('user_id', auth()->user()->id)
            ->with([])
            ->latest()
            ->get();

        return CalendarAgendaResource::collection($agendas);
    }

    public function store(Request $request)
    {
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
            'created_by' => auth()->user()->id,
        ]);

        return apiResponse(
            data: $agenda,
            message: 'Agenda Created Successfully'
        );
    }

    public function show(Agenda $agenda)
    {
        $agenda->start_date = $this->convert_date($agenda->start_date);
        $agenda->end_date = $this->convert_date($agenda->end_date);

        return apiResponse(
            data: $agenda->only(['title', 'description', 'start_date', 'end_date'])
        );
    }

    public function update(Agenda $agenda, Request $request)
    {
        $request->validate([
            'title' => 'required|min:2|max:250',
            'description' => 'required',
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date' => 'required|date_format:Y-m-d H:i:s',
        ]);
        $agenda->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return apiResponse(
            data: $agenda,
            message: 'Agenda Updated Successfully'
        );
    }

    public function destroy(Agenda $agenda)
    {
        $agenda->delete();

        return apiResponse(
            data: null,
            message: 'Agenda Deleted Successfully'
        );
    }

    private function convert_date($given_date)
    {
        // Create a DateTime object from the original date string
        $originalDateTime = new DateTime($given_date);

        // Format the DateTime object to the desired format
        $newDateString = $originalDateTime->format('Y-m-d\TH:i:s');

        return $newDateString;
    }
}
