<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\alumns;
use App\Helpers\JwtAuth;

class AlumnsController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => [
            'index',
            'show',
            ]]);
    }

    public function index(){
        $alumns = alumns::all();

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'alumns' => $alumns
        ]);
    }

    public function show($id){
        $alumn = alumns::find($id);                              
        if(is_object($alumn)){
            $data['code'] = 200;
            $data['status'] = 'success';
            $data['alumn'] = $alumn;
        } else {
            $data['code'] = 400;
            $data['status'] = 'error';
            $data['message'] = 'El alumno no existe';
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request){
        //Recoger los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);        
        if(!empty($params_array)){
            //conseguir Usuario Identificado
            $user = $this->getIdentity($request);

            //validar los datos
            $validate = \Validator::make($params_array, [
                'Name' => 'required',
                'LastName' => 'required',
                'Birthday' => 'required',
                'Gender' => 'required',
                'StudyLevel' => 'required',
                'Email' => 'required',
                'Phone' => 'required'
                
            ]);

            if($validate->fails()){
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado el alumno, Faltan datos'
                ];
            } else {
                //Guardar el alumno
                $alumn = new alumns();
                $alumn->Name = $params->Name;
                $alumn->LastName = $params->LastName;
                $alumn->SecondLastName = $params->SecondLastname;
                $alumn->Birthday = $params->Birthday;
                $alumn->Gender = $params->Gender;
                $alumn->StudyLevel = $params->StudyLevel;
                $alumn->Email = $params->Email;
                $alumn->Phone = $params->Phone;
                $alumn->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Se ha guardado el Alumno correctamente',
                    'alumn' => $alumn
                ];
            }            
            
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'envia los datos correctamente'
            ];
        }

        //Devolver respuesta
        return response()->json($data, $data['code']);
    }

    private function getIdentity($request){
        $jwt = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwt->checkToken($token, true);
        return $user;
    }
}
