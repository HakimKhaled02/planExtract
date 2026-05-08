<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Column;
use App\Models\Row;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ProjectController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        
        $totalProjects = $user->projects()->count();
        $pending = $user->projects()->where('status', 'pending')->count();
        $completed = $user->projects()->where('status', 'completed')->count();
        $cancelled = $user->projects()->where('status', 'cancelled')->count();
        
        $projectIds = $user->projects()->pluck('id');
        $totalColumns = Column::whereIn('project_id', $projectIds)->count();
        $totalRows = Row::whereIn('project_id', $projectIds)->count();
        
        $recentProjects = $user->projects()->latest()->take(5)->get();
        
        $recentActivity = Activity::latest()->take(8)->get();

        return view('dashboard', compact(
            'totalProjects', 'pending', 'completed', 'cancelled', 
            'totalColumns', 'totalRows', 'recentProjects', 'recentActivity'
        ));
    }

    public function index()
    {
        $projects = auth()->user()->projects()->with(['columns' => function($q) {
            $q->orderBy('order');
        }, 'rows.cellValues'])->latest()->paginate(10);
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:20480', // 20MB limit per image
        ]);

        $project = auth()->user()->projects()->create([
            'name' => $request->name,
            'status' => 'pending',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $project->addMedia($image)->toMediaCollection('images');
            }
            
            // ALWAYS Create standardized English columns
            $standardColumns = [
                0 => 'No',
                1 => 'Company',
                2 => 'Address',
                3 => 'Registration Number',
                4 => 'PIC',
                5 => 'Contact PIC',
                6 => 'Inspection Date',
                7 => 'Time',
                8 => 'Plant Registration Number',
                9 => 'Category',
                10 => 'Sub Type',
                11 => 'Plant Code',
                12 => 'Machine Serial Number',
                13 => 'CF Expiry Date',
                14 => 'Rate',
                15 => 'Status',
                16 => 'Remark'
            ];
            
            $dbColumns = [];
            foreach ($standardColumns as $order => $colName) {
                $dbColumns[$order] = $project->columns()->create([
                    'name' => $colName,
                    'type' => 'text',
                    'order' => $order
                ]);
            }
            
            $mediaItems = $project->getMedia('images');
            if ($mediaItems->count() > 0) {
                $paths = $mediaItems->map->getPath()->toArray();
                $pythonScript = base_path('extract.py');
                
                // Execute python script
                $command = "python3 " . escapeshellarg($pythonScript) . " " . implode(" ", array_map('escapeshellarg', $paths));
                $output = shell_exec($command);
                
                $data = json_decode($output, true);
                
                if (isset($data['tables']) && count($data['tables']) > 0) {
                    $table = $data['tables'][0]; // Just take first table for MVP
                    
                    if (count($table) > 1) { // Ensure there are rows beyond header
                        // Map PDF column indices (0-12) to Standard Column indices
                        $pdfToStandardMap = [
                            0 => 0,   // No
                            1 => 1,   // Company
                            2 => 2,   // Address
                            3 => 3,   // Registration Number
                            4 => 4,   // PIC
                            5 => 5,   // Contact PIC
                            6 => 8,   // Plant Registration Number
                            7 => 9,   // Category
                            8 => 10,  // Sub Type
                            9 => 11,  // Plant Code
                            10 => 12, // Machine Serial Number
                            11 => 13, // CF Expiry Date
                            12 => 14  // Rate
                        ];
                        
                        // Process rows (skip first row assuming it's the header)
                        for ($i = 1; $i < count($table); $i++) {
                            $rowData = $table[$i];
                            $row = $project->rows()->create(['order' => $i]);
                            
                            // Initialize all columns with empty values
                            foreach ($dbColumns as $order => $col) {
                                $row->cellValues()->create([
                                    'column_id' => $col->id,
                                    'value' => ''
                                ]);
                            }
                            
                            // Fill in extracted data based on mapping
                            foreach ($rowData as $pdfIndex => $cellValue) {
                                $pdfKey = is_numeric($pdfIndex) ? (int) $pdfIndex : $pdfIndex;
                                if (isset($pdfToStandardMap[$pdfKey])) {
                                    $standardIndex = $pdfToStandardMap[$pdfKey];
                                    $colId = $dbColumns[$standardIndex]->id;
                                    
                                    $row->cellValues()->where('column_id', $colId)->update([
                                        'value' => substr(trim($cellValue), 0, 1000)
                                    ]);
                                }
                            }
                        }
                        
                        $project->update(['status' => 'completed']);
                    } else {
                        $project->update(['status' => 'cancelled']);
                    }
                } else {
                    $project->update(['status' => 'cancelled']);
                }
            }
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project created and images processed.');
    }

    public function show(Project $project)
    {
        if ($project->user_id !== auth()->id()) abort(403);
        
        $project->load(['columns' => function($q) {
            $q->orderBy('order');
        }, 'rows.cellValues']);

        return view('projects.show', compact('project'));
    }

    public function destroy(Project $project)
    {
        if ($project->user_id !== auth()->id()) abort(403);
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    public function updateCell(Request $request, \App\Models\CellValue $cellValue)
    {
        // Simple authorization: check if project belongs to user
        if ($cellValue->row->project->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'value' => 'nullable|string|max:1000'
        ]);

        $cellValue->update([
            'value' => $request->value
        ]);

        return response()->json(['success' => true]);
    }
}
