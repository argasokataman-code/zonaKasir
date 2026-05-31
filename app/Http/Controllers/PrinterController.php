<?php

namespace App\Http\Controllers;

use App\Models\Tenants\Printer;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    public function index()
    {
        return $this->buildResponse()
            ->setData(Printer::all())
            ->setMessage('Data retrieved successfully')
            ->present();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'ip_address' => 'nullable',
            'port' => 'nullable',
            'driver' => 'nullable',
        ]);

        $data = $request->only('name', 'ip_address', 'port', 'driver');
        // Allow 'type' as an alias for 'driver' for API compatibility
        if (empty($data['driver']) && $request->has('type')) {
            $data['driver'] = $request->input('type');
        }
        if (empty($data['driver'])) {
            $data['driver'] = '';
        }

        Printer::create($data);

        return $this->buildResponse()
            ->setMessage('Data saved successfully')
            ->present();
    }

    public function update(Request $request, Printer $printer)
    {
        $request->validate([
            'name' => 'nullable',
            'ip_address' => 'nullable',
            'port' => 'nullable',
            'driver' => 'nullable',
        ]);

        $printer->update($request->only('name', 'ip_address', 'port', 'driver'));

        return $this->buildResponse()
            ->setMessage('Data updated successfully')
            ->present();
    }

    public function destroy(Printer $printer)
    {
        $printer->delete();

        return $this->buildResponse()
            ->setMessage('Data deleted successfully')
            ->present();
    }
}
