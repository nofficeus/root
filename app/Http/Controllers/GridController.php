<?php

namespace App\Http\Controllers;

use App\Models\Grid;
use Illuminate\Http\Request;

class GridController extends Controller
{
    /**
     * Get all grids for page
     *
     * @param Request $request
     * @return Grid[]
     * @throws \Illuminate\Validation\ValidationException
     */
    public function all(Request $request)
    {
        $data = $this->validate($request, [
            'page' => 'required|exists:grids,page'
        ]);

        return Grid::visible()->withOrder()
            ->where('page', $data['page'])->get();
    }

    /**
     * Set grid dimensions
     *
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setDimensions(Request $request)
    {
        $data = $this->validate($request, [
            'page'         => 'required|exists:grids,page',
            'dimensions'   => 'required|array',
        ]);

        foreach ($data['dimensions'] as $name => $dimensions) {
            $query = Grid::where('page', $data['page'])->where('name', $name);

            if ($grid = $query->first()) {
                $grid->update(['dimensions' => $dimensions]);
            }
        }
    }
}
