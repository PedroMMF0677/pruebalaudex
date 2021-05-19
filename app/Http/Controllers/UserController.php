<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

class UserController extends Controller
{
    public $User;
    
    public function __construct(){
        $this->User = new User();
        $this->jwtAuth = new \JwtAuth();
    }

    public function register(Request $request) {            
        //recojemos los datos por post        
        $json = $request->input('json', null);        
        $params = json_decode($json);
        $params_array = json_decode($json, true); 
          
        if(!empty($params)){            
            //limpiar datos
            $params_array = \array_map('trim', $params_array);

            //utilizamos la funcion Validator de Laravel
            $validate = \Validator::make($params_array, [
                'Name' => 'required',
                'Surname' => 'required',
                'Email' => 'required|email|unique:users',
                'Password' => 'required'
            ]);

            if($validate->fails()){
                //validacion fallo
                $data['status'] = 'error';
                $data['code'] = 404;
                $data['message'] = 'The user not created succesfull';
                $data['errors'] = $validate->errors();            
            } else {
                //validacion correcta

                //cifrar la contraseña
                $pwd = hash('sha256', $params->Password);                           

                //crear el usuario                
                $this->User->Name = $params_array['Name'];
                $this->User->Surname = $params_array['Surname'];
                $this->User->Email = $params_array['Email'];
                $this->User->Password = $pwd;
                $this->User->Role = 'ROLE_USER';
            
                $this->User->save();            
            
                $data['status'] = 'success';
                $data['code'] = 200;
                $data['message'] = 'User created succesfull';
                $data['user'] = $this->User;
            }
            
        } else {
            $data['status'] = 'error';
            $data['code'] = 404;
            $data['status'] = 'The data send is empty';
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request){        
        //recibir datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        

        //validar datos
        $validate = \Validator::make($params_array,[
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validate->fails()){
            //fallo la validación
            $singUp['status'] = 'error';
            $singUp['code'] = 404;
            $singUp['message'] = 'Fallo autenticación del usuario';
            $singUp['errors'] = $validate->errors();
        } else {
            //cifrar la contraseña
            $pwd = hash('sha256', $params->password);
            //devolver token o datos
            $singUp = $this->jwtAuth->singup($params->email, $pwd);

            if(!empty($params->gettoken)){
                $singUp = $this->jwtAuth->singup($params->email, $pwd, true);
            }
        }
        
        return response()->json($singUp, 200);
    }

    public function update(Request $request){
        //Comprobamos si esta autentificado

        $token = $request->header('Authorization');
        $checkToken = $this->jwtAuth->checkToken($token);        
        //recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);


        if($checkToken && !empty($params_array)){

            //sacar usuario autentificado
            $user =  $this->jwtAuth->checkToken($token, true);
            
            //validar los datos

            $validate = \Validator::make($params_array, [
                'name' => 'required', 
                'surname' => 'required',
                'email' => 'required|email|unique:users,' . $user->sub
                ]);
            // quitar los campos que no queremos actualizar
            unset($params_array['sub']);
            unset($params_array['iat']);
            unset($params_array['exp']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['create_at']);
            unset($params_array['remember_token']);

            //Actualizar usuario en db
            $user_update = User::where('id', $user->sub)->update($params_array);

                $data['code'] = 200;
                $data['status'] = 'succes';
                $data['user'] = $user;                
                $data['changes'] = $params_array;                

        } else {
            //Devoldemos un mensaje de error
            $data['status'] = 'error';
            $data['message'] = 'El Usuario no esta Autentificado';
            $data['code'] = 400;            
        }

        return response()->json($data, 200);
    }

    public function upload(Request $request){

        // recoger los datos de la peticion
        $image = $request->file('file0');
        

        $validate = \Validator::make($request->all(), [
            'file0' => 'required|mimes:jpg,jpeg,png,gif'
        ]);
        

        //guardar imagen
        if(!$image || $validate->fails()){
            $data['code'] = 400;
            $data['status'] = 'error';
            $data['message'] = 'Error al subir la imagen';
            $data['errors'] = $validate->fails();
        } else {
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            //Devolvemos el arreglo
            $data['code']= 200;
            $data['status']= 'success';
            $data['image']= $image_name;
        }
        
        return response()->json($data, $data['code']);
    }

    public function getImage($filename){

        $isset = \Storage::disk('users')->exists($filename);
        if($isset){
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data['status'] = 'error';
            $data['code'] = 404;
            $data['message'] = 'No existe la imagen';
        }
        return response()->json($data, 200);   
    }

    public function detail($id){
        $user = User::find($id);

        if(is_object($user)){
            $data['code'] = 200;
            $data['status'] = 'success';
            $data['user'] = $user;
        } else {
            $data['code'] = 400;
            $data['status'] = 'error';
            $data['message'] = 'El usuario no existe';
        }

        return response()->json($data, $data['code']);
    }
}
