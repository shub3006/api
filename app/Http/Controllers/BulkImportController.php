<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use App\Models\Type;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class BulkImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);

        DB::beginTransaction();

        try {
            while (($data = fgetcsv($handle)) !== FALSE) {
                list($name, $alias, $description, $category, $collection, $substore, $inventory) = $data;

                // Insert into Products
                $product = Product::updateOrCreate(
                    ['name' => $name, 'Alias' => $alias],
                    ['description' => $description]
                );

                // Insert into Types
                $type = Type::updateOrCreate(
                    ['category' => $category, 'collection' => $collection]
                );

                // Insert into Stores
                Store::updateOrCreate(
                    ['substore' => $substore],
                    ['inventory' => $inventory]
                );
            }

            fclose($handle);
            DB::commit();
            return response()->json(['message' => 'Data imported successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to import data'], 500);
        }
    }

}
