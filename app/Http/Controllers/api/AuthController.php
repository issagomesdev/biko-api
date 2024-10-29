<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\api\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Illuminate\Http\JsonResponse;
   
class AuthController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'cpf' => 'required|string|unique:users',
            'state' => 'required|string',
            'city' => 'required|string',
            'neighborhood' => 'required|string',
            'categories' => 'array'
        ]);
   
        if($validator->fails()){
            $errors = $validator->errors()->all();
            return $this->sendError('Falha no cadastro', $errors);       
        }
   
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        if(count(($request->categories)) > 0) {
            $user->categories()->attach($request->categories);
        }
        $success['token'] = $user->createToken('MyApp')->plainTextToken;
        $success['data'] =  $user;
   
        return $this->sendResponse($success, 'Cadastro realizado com sucesso!');
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request): JsonResponse
    {
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
            $user = Auth::user(); 
            $user->load('categories');
            $success['token'] =  $user->createToken('MyApp')->plainTextToken; 
            $success['data'] =  $user;
   
            return $this->sendResponse($success, 'Login realizado com sucesso!');
        } 
        else{ 
            return $this->sendError('Verifique suas credenciais e tente novamente.', ['error'=>'Unauthorised']);
        } 
    }

    public function logout(){
        auth()->user()->tokens()->delete();

        return $this->sendResponse(null, 'Desconectado');
    }
}