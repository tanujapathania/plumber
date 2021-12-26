<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ApiBaseController;

class MaterialController extends ApiBaseController
{    
    /**
     * create
     *
     * @param  mixed $request
     * @return void
     */
    public function create(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'project_id'        => 'required|numeric|exists:projects,id',
            'description'       => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendSingleFieldError($validator->errors()->first(),201,201);
        }
        $inputs  = $request->all();
        $inputs['request_user_id'] = \Auth::user()->id;

        $material = \App\Models\Material::create($inputs);

        return $this->sendResponse($material, 'Request generated successfully.',200,200);
    }
    
    /**
     * addAdminRemarks
     *
     * @param  mixed $request
     * @return void
     */
    public function addAdminRemarks(Request $request)
    {
        if (\Auth::user()->role == 1) {
            $validator = \Validator::make($request->all(), [
                'material_id'       => 'required|numeric|exists:materials,id',
                'project_id'        => 'required|numeric|exists:projects,id',
                'admin_remarks'     => 'required', 
                'status'            => 'required|numeric', 
                'issue_date'        => 'required_if:status,=,1',
            ]);
            if ($validator->fails()) {
                return $this->sendSingleFieldError($validator->errors()->first(),201,201);
            }

            $material = \App\Models\Material::find($request->material_id);
            $material->update($request->all());

            return $this->sendResponse((object) [], 'Remarks added successfully.',200,200);
        }

        return $this->sendSingleFieldError('No access!',401,401);
    }
    
    /**
     * requestList
     *
     * @param  mixed $request
     * @return void
     */
    public function requestList(Request $request)
    {
        if (\Auth::user()->role == 1) {
            $materials = \App\Models\Material::with(['user','project'])
            ->where('status',\Config::get('constant.materials.status.pending'))
            ->orderBy('id','DESC')
            ->get();

            if (!blank($request->employee_id)) {dd();
                $materials = $materials->where('request_user_id	',$request->employee_id);
            }elseif(!blank($request->date_from) &&  !blank($request->date_to)){
                $materials = $materials->whereBetwwen('created_at',[$request->date_from,$request->date_to]);
            }elseif(!blank($request->employee_id) && !blank($request->date_from) &&  !blank($request->date_to)){
                $materials = $materials->where('request_user_id	',$request->employee_id)
                            ->whereBetwwen('created_at',[$request->date_from,$request->date_to]);
            }

            return $this->sendResponse($materials, 'Materials request list.',200,200);
        }
        return $this->sendSingleFieldError('No access!',401,401);
    }
    
    /**
     * addReceiverRemarks
     *
     * @param  mixed $request
     * @return void
     */
    public function addReceiverRemarks(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'material_id'       => 'required|numeric|exists:materials,id',
            'project_id'        => 'required|numeric|exists:projects,id',
            'user_remarks'     => 'required'
        ]);
        if ($validator->fails()) {
            return $this->sendSingleFieldError($validator->errors()->first(),201,201);
        }

        $inputs = $request->all();
        $inputs['status'] = \Confin::get('constant.materials.status.received'); 

        $material = \App\Models\Material::find($request->material_id);
        $material->update($inputs);

        return $this->sendResponse((object) [], 'Remarks added successfully.',200,200);
    }
}
