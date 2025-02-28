<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //Metodo para mostrar los datos de un usuario
    public function showOne($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['message' => '', 'data' => $user->actions], 200);
    }
    //Metodo para validar y crear un nuevo usuario
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'dni' => 'required|string|unique:users',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $user = User::create([
            'name' => $request->get('name'),
            'lastname' => $request->get('lastname'),
            'email' => $request->get('email'),
            'dni' => $request->get('dni'),
            'password' => Hash::make($request->get('password')),
            'role' => 'user',
        ]);
        return response()->json(['message' => 'Usuario creado', 'data' => $user], 200);
    }
    //Metodo para mostrar los datos de un usuario mediante su ID
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['message' => '', 'data' => $user], 200);
    }
    //Metodo para mostrar todos los usuarios
    public function showAll()
    {
        $user = User::all();
        return response()->json(['message' => '', 'data' => $user], 200);
    }
    //Metodo para obtener las actividades de un usuario
    public function actions($id)
    {
        $users = User::select(
            'actions.name as action_name',
            'actions.capacity as action_capacity',
            'users.id as user_id',
            'users.name as user_name',
            'users.lastname as user_lastname',
            'users.email as user_email',
            'users.dni as user_dni'
        )
            ->join('action_user', 'users.id', '=', 'action_user.user_id')
            ->join('actions', 'actions.id', '=', 'action_user.action_id')
            ->where('action_user.action_id', '=', $id)
            ->get();

        return response()->json(['message' => '', 'data' => $users], 200);
    }
    //Metodo para actualizar los valores de un usuario
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|nullable',
            'dni' => 'sometimes|string|unique:users,dni,' . $user->id,
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $user->name = $request->get('name', $user->name);
        $user->lastname = $request->get('lastname', $user->lastname);
        $user->email = $request->get('email', $user->email);
        $user->dni = $request->get('dni', $user->dni);
        if ($request->has('password') && $request->get('password') !== null) {
            $user->password = Hash::make($request->get('password'));
        }
        $user->save();
        return response()->json(['message' => 'Usuario actualizado', 'data' => $user], 200);
    }
    //Metodo para eliminar un usuario
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado', 'data' => $user], 200);
    }
    //Metodo para iniciar sesion
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            return response()->json($user);
        }
        return response()->json(['message' => 'Credenciales incorrectas'], 401);
    }
    //Metodo para asociar un usuario a una actividad
    public function join(Request $request){
        $validator = Validator::make(request()->all(), [
            'user_id' => 'required|integer',
            'action_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        $user = User::where('id', request()->get('user_id'))->firstOrFail();
        $action = Action::where('id', request()->get('action_id'))->firstOrFail();
        if ($user->actions()->save($action)){
            return response()->json(['message'=>'Usuario asociado','data'=>$user, $action], 200);
        }
    }
    //Metodo para desasociar un usuario a una actividad
    public function joinDelete(Request $request)
    {
        $user = User::findOrFail($request->user_id);
        $action = Action::findOrFail($request->action_id);

        if ($user->actions()->detach($action->id)) {
            return response()->json(['message' => 'Usuario desapuntado correctamente'], 200);
        }

        return response()->json(['message' => 'No se encontró la inscripción'], 404);
    }
    //Metodo para ver a que actividades esta asociado un usuario
    public function isEnroled(Request $request) {
        $user = User::findOrFail($request->user_id);

        if (!$user) {
            return response()->json(['enrolled' => false]);
        }
        $isEnrolled = $user->actions()->where('action_id', $request->action_id)->exists();
        return response()->json(['enrolled' => $isEnrolled]);
    }
}

