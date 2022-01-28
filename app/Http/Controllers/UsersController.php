<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Collection;
use App\Models\Deck;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isEmpty;

class UsersController extends Controller
{
    /**
     * Función para registrar nuevos usuarios y cifrar la contraseña
     */
    public function register(Request $req){
        
        $answer = ['status' => 1, 'msg' => ''];

        $dataUser = $req -> getContent();

        // Validar los campos
        $validator = Validator::make(json_decode($req->getContent(), true), [
            'name' => 'required|max:255',
            'role' => 'required|in:Particular,Profesional,Administrador',
            'email' => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix|unique:users|max:255',
            'password' => 'required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/'
        ]);

        // Try catch para escribir en la base de datos
        try{
            // Validar el json
            $dataUser = json_decode($dataUser);

            if($validator->fails()){
                $answer['msg'] = "Ha ocurrido un error". $validator->errors();
            }else{

                // Creas un nuevo objeto que es el modelo user antes importado
                $user = new User();

                // Validar los datos
                $user -> name = $dataUser -> name;
                $user -> role = $dataUser -> role;
                $user -> email = $dataUser -> email;
                $user -> password = Hash::make($dataUser -> password);
                
                $user -> save();
                $answer['msg'] = "Usuario registrado correctamente";
            }

        }catch(\Exception $e){
            $answer['msg'] = $e -> getMessage();
            $answer['status'] = 0;
        }
        
        return response() -> json($answer);
    }

    /**
     * Función para realizar el login
     */
    public function login(Request $req){
        $answer = ['status'=>1, 'msg'=>''];

        $user = "";

        // Compruebo campos name y password
        // Con el input compruebo que exista el campo name y no esté vacío
        if($req->input('name') != ""){
            $user = User::where('name', $req->input('name'))->first();
        }else{
            $answer['info']='Introduce un nombre para continuar';
        }

        // Compruebo que exista el campo password y no esté vacío
        if($req->input('password') == ""){
            $answer['info']='Introduce una password para continuar';
        }

        // Si hay user compruebo si la contraseña guardada y la introducida coinciden y genero el token
        if($user){
            if(Hash::check($req->input('password'), $user->password)){
                try{
                    do{
                        $token = Hash::make(now(). $user->id);

                        $user->api_token = $token;

                        $user->save();

                        $exit = User::where('name', $req->input('name'))->value('api_token');
                    }while(!$exit);

                    $answer['msg']= "Código de inicio de sesión: ". $user->api_token;
                }catch(\Exception $e){
                    $answer['msg'] = $e -> getMessage();
                    $answer['status'] = 0;
                }
            }else{
                $answer['msg'] = 'Contraseña incorrecta';
            }
        }else{
            $answer['error'] = 'No existen usuarios con ese nombre';
        }

        return response()->json($answer);
    }

    /**
     * Función para recuperar contraseña
     */
    public function passwordRecovery(Request $req){
        $answer = ['status' => 1, 'msg' => ''];

        $email = $req->input('email');

        $Pass_pattern = "/^\S*(?=\S{6,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/";

        try{
            if($req->has('email')){
                $user = User::where('email', $email)->first();

                if($user){
                    do{
                        $password = Str::random(6);
                    }while(!preg_match($Pass_pattern, $password));
                    $user->password = Hash::make($password);
                    $user->save();
                    $answer['msg']="Se ha reestablecido la contraseña";
                    $answer['pass']="Nueva contraseña: ". $password;
                }else{
                    $answer['msg']="No existe este usuario";
                }
            }else{
                $answer['msg']= "Introduce un email";
            }
        }catch(\Exception $e){
            $answer['status'] = 0;
            $answer['msg'] = "Se ha producido un error: ".$e->getMessage();
        }

        return response()->json($answer);

    }

    /**
     * Función para registrar nuevas cartas SOLO ADMINISTRADOR
     */
    public function registerCards(Request $req){
        
        $answer = ['status' => 1, 'msg' => ''];

        $user = $req->user; // Token del usuario que va a realizar el registro, DEBE ser un Administrador
        
        // Coger el contenido del postman en formato json
        $dataCard = $req -> getContent();
        $dataCard = json_decode($dataCard);

        // Comparar la collection escrita por el postman con las que existen en mi tabla de la bbdd
        $collectionInPostman = $dataCard->collection_id;
        $collectionInTable = DB::table('collections')->where('id', $collectionInPostman)->first(); // Busco en la tabla collections esa colección concreta y la meto en una variable

        // Validar los campos
        $validator = Validator::make(json_decode($req->getContent(), true), [
            'name' => 'required|max:255',
            'description' => 'required',
            'collection_id' => 'required'
        ]);

        // Try catch para escribir en la base de datos
        try{
            if($validator->fails()){
                $answer['msg'] = "Ha ocurrido un error". $validator->errors();
            }else{
                    // Si la colección existe la añado al modelo de nueva carta
                    if($collectionInTable){
                        $card = new Card();
                        $card -> name = $dataCard -> name;
                        $card -> description = $dataCard -> description;
                        $card -> save();
                        $answer['msg'] = "Carta registrada correctamente";

                        // Añadir la nueva relación a la tabla intermedia cards_collection
                        $deck = new Deck();
                        $deck -> card_id = $card -> id;
                        $deck -> collection_id = $collectionInTable -> id;
                        $deck -> save();
                    }else{
                        $answer['msg'] = "Esa colección no existe. Debe crearla antes de añadir la carta";
                    }
            }

        }catch(\Exception $e){
            $answer['msg'] = $e -> getMessage();
            $answer['status'] = 0;
        }
        
        return response() -> json($answer);
    }

    /**
     * Función para registrar nuevas colecciones SOLO ADMINISTRADOR
     * No puede crarse vacía, debe tener al menos una carta dentro
     */
    public function registerCollections(Request $req){
        
        $answer = ['status' => 1, 'msg' => ''];

        $user = $req->user; // Token del usuario que va a realizar el registro, DEBE ser un Administrador

        $dataCollection = $req -> getContent();
        $dataCollection = json_decode($dataCollection);

        $idCardPostman = $dataCollection->idCard; // Coges el nombre de la primera carta desde el postman
        $cardInTable = DB::table('cards')->where('id', $idCardPostman)->first(); // Busco la carta en la tabla de cartas para luego ver si existe

        // Validar los campos
        $validator = Validator::make(json_decode($req->getContent(), true), [
            'name' => 'required|max:255',
            'symbol' => 'required',
            'date' => 'required',
            'idCard' => 'required'
        ]);

        // Try catch para escribir en la base de datos
        try{
            if($validator->fails()){
                $answer['msg'] = "Ha ocurrido un error". $validator->errors();
            }else{
                    $collection = new Collection();
                    $collection -> name = $dataCollection -> name;
                    $collection -> symbol = $dataCollection -> symbol;
                    $collection -> date = $dataCollection -> date; 
                    $collection -> save();

                if($cardInTable){
                    $answer['msg'] = "Colección registrada correctamente";
                }else{
                    // Si no existe la carta, creo la carta y la colección
                    $card = new Card();
                    $card -> name = "Default name";
                    $card -> description = "Default description";
                    $card->save();
                    $answer['msg'] = "Carta y colección creadas";
                    
                    $cardInTable = $card; // $card pasa a ser $cardInTable para poder usar su id al añadir la relación a la tabla intermedia
                }

                // Añadir la nueva relación a la tabla intermedia cards_collection
                $deck = new Deck();
                $deck -> card_id = $cardInTable -> id;
                $deck -> collection_id = $collection -> id;
                $deck -> save();
            }

        }catch(\Exception $e){
            $answer['msg'] = $e -> getMessage();
            $answer['status'] = 0;
        }
        
        return response() -> json($answer);
    }

    /**
     * Función para asociar una carta a otras colecciones SOLO ADMINISTRADOR
     * Se irán asociando las cartas a las colecciones en la tabla intermedia cards_collections
     */
    public function addCards(Request $req){
        
        $answer = ['status' => 1, 'msg' => ''];

        $card = $req -> getContent(); // Datos de la carta a modificar
        $card = json_decode($card);

        // Recibo el nombre de una carta y una collection que ya existen
        $idCard = $req->input('idCarta');
        $idCollection = $req->input('idCollection');
        
        // Busco esa carta en la tabla de cartas
        $cardToAdd = DB::table('cards')->where('id', $idCard)->first();
        $collection = DB::table('collections')->where('id', $idCollection)->first();
        
        // Busco el id de la carta que quiero añadir para luego comprobar que no esté ya añadida a esa colección
        $cardId = $cardToAdd->id;
        $collectionId = $collection->id;
        
        $check=DB::table('cards_collections')
                        ->select('card_id', 'collection_id')
                        ->where('card_id', $cardId)
                        ->where('collection_id', $collectionId)
                        ->first();
        
        try {
            if($cardToAdd && $collection){
                // Si el id de la carta ya existe en esa colección no la puedo añadir de nuevo
                if($check){
                    $answer['msg'] = "La carta ya había sido añadida a esta colección";
                }else{
                    // Añado la carta a la collection, creando un Deck
                    $deck = new Deck();
                    $deck -> card_id = $cardToAdd -> id;
                    $deck -> collection_id = $collection -> id;
                    $deck -> save();
                    $answer['msg'] = "La carta ha sido añadida a la colección";
                }
                
            }else{
                $answer['msg'] = "La carta o la colección no existen"; 
            }
        } catch (\Exception $e) {
            $answer['msg'] = $e -> getMessage();
            $answer['status'] = 0;
        }
        
        return response() -> json($answer);
    }

    /**
     * Función para vender cartas SOLO PROFESIONALES Y PARTICULARES
     * El Administrador ha registrado una lista de cartas oficial, ahora cada usuario puede vender las cartas que tenga pero cuyos nombres aparezcan en esa lista, 
     * porque si no podría inventarse las cartas, es decir, no se puede poner a la venta una carta que no haya registrado un Administrador previamente.
     * Es hacer un registro pero comprobando que el id de la carta que el user quiera registrar ya exista en la tabla cards.
     */
    public function sell(Request $req){
        $answer = ['status' => 1, 'msg' => ''];

        $user = $req->user; // Token del usuario que va a realizar el registro de la venta
        
        // Coger el contenido del postman en formato json
        $sellingData = $req -> getContent();
        $sellingData = json_decode($sellingData);
        
        // Validar los campos
        $validator = Validator::make(json_decode($req->getContent(), true), [
            'number_cards' => 'required',
            'price' => 'required',
            'idCard' => 'required'
        ]);

        $idCardPostman = $sellingData->idCard; // Recibo el id de la carta que se quiere vender y la busco en la tabla cards para comprobar que esté registrada
        $checkCard = DB::table('cards')->where('id', $idCardPostman)->first();

        try{
            if($validator->fails()){
                $answer['msg'] = "Ha ocurrido un error". $validator->errors();
            }else{
                if($checkCard){
                    $sale = new Sale();
                    $sale -> number_cards = $sellingData -> number_cards;
                    $sale -> price = $sellingData -> price;
                    $sale -> card_id = $idCardPostman;
                    $sale -> user_id = $user -> id;
                    $sale -> save();
                    $answer['msg'] = "Venta registrada correctamente";
                }else{
                    $answer['msg'] = "La carta que intenta vender no existe en nuestro sistema";
                }            
            }

        }catch(\Exception $e){
            $answer['msg'] = $e -> getMessage();
            $answer['status'] = 0;
        }
        
        return response() -> json($answer);
    }

    /**
     * Función para buscar cartas para VENDER. SOLO PROFESIONALES Y PARTICULARES
     * El Administrador ha registrado una lista de cartas, ahora cada usuario puede buscar sobre esa lista una carta concreta y poner a la venta las 
     * que posea de ese tipo. Es decir, no se puede poner a la venta una carta que no haya registrado un Administrador previamente.
     */
    public function searchForSelling(Request $req){
        $answer = ['status' => 1, 'msg' => ''];

        $search = $req->input("name"); // Nombre de la carta a buscar

        // Se le devuelve una lista de cartas que coincidan con la búsqueda
        $searchResults = DB::table('cards')
            ->where('cards.name', 'like', '%'.$search.'%')
            ->select(
                'cards.id',
                'cards.name'
            )
            ->get();
            
        try{
            if($searchResults){
                $answer['msg'] = "Estos son tus resultados:";
                $answer['data'] = $searchResults;
            }
            
        }catch(\Exception $e){
            $answer['msg'] = $e -> getMessage();
            $answer['status'] = 0;
        }

        return response() -> json($answer);
    }

    /**
     * Función para buscar cartas para COMPRAR
     * La diferencia con el anterior es que aquí sólo se muestran las cartas que estén a la venta, osea en la tabla intermedia
     * Recibes el name de la carta, con ese name sacas su id de la tabla cards y lo buscas en la tabla sales para averiguar si hay alguna a la venta. Si existen 
     * algunas a la venta las devuelves ordenadas por precio ascendente y CON EL NOMBRE DE LA CARTA Y DEL VENDEDOR.
     */
    public function searchForBuying(Request $req){
        $answer = ['status' => 1, 'msg' => ''];

        // Nombre de la carta a buscar
        $search = $req->input("name");

        try{
            if($search){
                $answer['msg'] = "Resultados de la búsqueda:";

                $finalResults['data'] = DB::table('sales')
                    ->join('cards','sales.card_id', '=', 'cards.id')
                    ->join('users','sales.user_id', '=', 'users.id')
                    ->select('cards.name as Nombre_Carta','sales.number_cards', 'sales.price','users.name')
                    ->where('cards.name','like','%'.$search.'%')
                    ->orderBy('price','asc')
                    ->get();

                $answer['data'] = $finalResults;

                }else{
                    $answer['msg'] = "Introduzca un término a buscar";
                }
        }catch(\Exception $e){
            $answer['msg'] = $e -> getMessage();
            $answer['status'] = 0;
        }

        return response() -> json($answer);
    }

}
