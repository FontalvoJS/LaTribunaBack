<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComments;
use App\Models\PostReactions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class PostController extends Controller
{

    public function uploadContentImages(Request $request)
    {
        try {
            // Validar la solicitud para múltiples imágenes
            $validator = Validator::make($request->all(), [
                'images' => 'required|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
            $urls = [];
            foreach ($request->file('images') as $image) {
                // Obtener el nombre original y el mime type de la imagen
                $originalName = $image->getClientOriginalName();

                // Generar el path para almacenar la imagen en la carpeta 'public/images/content_images'
                $path = $image->storeAs('images/content_images', $originalName, 'public');

                // Generar la URL de la imagen
                $url = Storage::url("http:localhost/beerclub_backend/public" . $path);
                array_push($urls, $url);
            }
            return response()->json(['urls' => $urls], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar las imágenes: '], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function createPost(Request $request)
    {
        try {
            // Definir las reglas de validación
            $error_messages = [
                'title.required' => 'El título es obligatorio',
                'title.string' => 'El título debe ser una cadena de texto',
                'title.max' => 'El título no debe exceder los 255 caracteres',
                'title.unique' => 'El título ya existe',
                'image.required' => 'La imagen es obligatoria',
                'image.image' => 'La imagen debe ser una imagen',
                'image.mimes' => 'La imagen debe ser una imagen de tipo: jpeg, png, jpg, gif, svg',
                'image.max' => 'La imagen no debe exceder los 2MB',
                'description.required' => 'La descripción es obligatoria',
                'description.string' => 'La descripción debe ser una cadena de texto',
                'description.max' => 'La descripción no debe exceder los 1000 caracteres',
                'tags.required' => 'Los tags son obligatorios',
                'tags.string' => 'Los tags deben ser una cadena de texto',
                'tags.max' => 'Los tags no deben exceder los 255 caracteres',
                'author.required' => 'El autor es obligatorio',
                'author.string' => 'El autor debe ser una cadena de texto',
                'author.max' => 'El autor no debe exceder los 255 caracteres',
                'category.required' => 'La categoría es obligatoria',
                'category.string' => 'La categoría debe ser una cadena de texto',
                'category.in' => 'La categoría debe ser una de las siguientes: pronosticos, analisis, rumores, criticas',
                'content.required' => 'El contenido es obligatorio',
                'content.string' => 'El contenido debe ser una cadena de texto',
            ];
            $rules = [
                'title' => 'required|string|max:255|unique:posts',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // max 2MB
                'description' => 'required|string|max:1000',
                'tags' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'category' => 'required|string|in:Analisis,Rumores,Pronosticos,Criticas',
                'content' => 'required|string',
            ];

            // Validar la solicitud
            $validator = Validator::make($request->all(), $rules, $error_messages);

            // Si la validación falla, responder con errores en JSON
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }

            // Crear el slug a partir del título
            $title = $request->input('title');
            $slug = Str::slug($title);

            // Verificar si el slug ya existe en la base de datos y generar uno único
            while (Post::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . Str::random(4);
            }

            // Manejar la imagen
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('images/cover_images', 'public');

                if (!$imagePath) {
                    return response()->json(['error' => 'Error al subir la imagen.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['error' => 'No se encontró la imagen.'], Response::HTTP_BAD_REQUEST);
            }

            // Aquí puedes guardar los datos en la base de datos
            $article = new Post();
            $article->title = $title;
            $article->slug = $slug; // Asignar el slug al artículo
            $article->image = $imagePath;
            $article->description = $request->input('description');
            $article->tags = $request->input('tags');
            $article->author = $request->input('author');
            $article->category = $request->input('category');
            $article->content = $request->input('content');
            $article->active = true;
            $article->save();

            // Responder con éxito en JSON
            return response()->json([
                'message' => 'Artículo subido con éxito.',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al subir el artículo: '], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function updatePost(Request $request)
    {
        try {
            // Definir las reglas de validación
            $error_messages = [
                'title.required' => 'El título es obligatorio',
                'title.string' => 'El título debe ser una cadena de texto',
                'title.max' => 'El título no debe exceder los 255 caracteres',
                'description.required' => 'La descripción es obligatoria',
                'description.string' => 'La descripción debe ser una cadena de texto',
                'description.max' => 'La descripción no debe exceder los 1000 caracteres',
                'tags.required' => 'Los tags son obligatorios',
                'tags.string' => 'Los tags deben ser una cadena de texto',
                'tags.max' => 'Los tags no deben exceder los 255 caracteres',
                'author.required' => 'El autor es obligatorio',
                'author.string' => 'El autor debe ser una cadena de texto',
                'author.max' => 'El autor no debe exceder los 255 caracteres',
                'category.required' => 'La categoría es obligatoria',
                'category.string' => 'La categoría debe ser una cadena de texto',
                'category.in' => 'La categoría debe ser una de las siguientes: analisis,rumores,pronosticos,criticas',
                'content.required' => 'El contenido es obligatorio',
                'content.string' => 'El contenido debe ser una cadena de texto',
                'slug.required' => 'El slug es obligatorio',
                'slug.string' => 'El slug debe ser una cadena de texto',
                'editing.required' => 'El campo editing es obligatorio',

            ];
            $rules = [
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'tags' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'category' => 'required|string|in:Analisis,Rumores,Pronosticos,Criticas',
                'content' => 'required|string',
                'slug' => 'required|string',
                'editing' => 'required',
            ];

            // Validar la solicitud
            $validator = Validator::make($request->all(), $rules, $error_messages);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('images/cover_images', 'public');

                if (!$imagePath) {
                    return response()->json(['error' => 'Error al subir la imagen.'], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
            $data = $validator->validated();
            if (!empty($data['editing']) && $data['editing'] == 'true') {
                $post = Post::where('slug', $data['slug'])->first();
                $slug = Str::slug($data['title']);

                // Verificar si el slug ya existe en la base de datos y generar uno único
                while (Post::where('slug', $slug)->exists()) {
                    $slug = $slug . '-' . Str::random(4);
                }
                $post->update([
                    'content' => $data['content'],
                    'title' => $data['title'],
                    'image' => $imagePath ?? $post->image,
                    'description' => $data['description'],
                    'tags' => $data['tags'],
                    'author' => $data['author'],
                    'category' => $data['category'],
                    'slug' => $slug,
                ]);
                $post->save();
                return response()->json(['message' => 'Artículo actualizado con éxito.'], Response::HTTP_OK);
            } else {
                return response()->json(['error' => 'No se puede actualizar el post.'], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el artículo: '], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPreviewPosts()
    {
        try {
            $latest4Posts = Post::select('slug', 'title', 'image', 'category', 'created_at as date')->where("active", 1)->orderBy('created_at', 'desc')->take(10)->get();
            $latest4Posts->map(function ($post) {
                $post->image = "http://localhost/beerclub_backend/public/storage/" . $post->image;
                return $post;
            });
            return response()->json($latest4Posts, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los posts: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getPostBySlug($slug)
    {
        try {
            $post = Post::select("title", "image", "description", "tags", "author", "category", "content", "slug", "created_at")->where('slug', $slug)->first();
            $post->image = "http://localhost/beerclub_backend/public/storage/" . $post->image;

            $latestPosts = Post::select("title", "image", "description", "tags", "author", "category", "content", "slug", "created_at")->orderBy('created_at', 'desc')->take(5)->get();
            foreach ($latestPosts as $latestPost) {
                $latestPost->image = "http://localhost/beerclub_backend/public/storage/" . $latestPost->image;
            }

            return response()->json([
                'Slug' => $post,
                'More' => $latestPosts
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener el post: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function deletePost($slug)
    {
        try {
            $post = Post::where('slug', $slug)->first();
            $post->active = false;
            $post->save();
            return response()->json(['message' => 'Artículo eliminado correctamente.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar el artículo: '], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function postReaction(Request $request)
    {
        try {
            $error_messages = [
                'option.required' => 'La opción es obligatoria',
                'option.string' => 'La opción debe ser una cadena de texto',
                'option.in' => 'La opción debe ser una de las siguientes: like, dislike',
                'article.required' => 'El artículo es obligatorio',
                'article.string' => 'El artículo debe ser una cadena de texto',
            ];

            $validator = Validator::make($request->all(), [
                'option' => 'required|string|in:like,dislike',
                'article' => 'required|string',
            ], $error_messages);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }

            $post = Post::where('slug', $request->article)->first();
            if (!$post) {
                return response()->json(['error' => 'El artículo no existe'], Response::HTTP_NOT_FOUND);
            }

            $reaction = PostReactions::where('post_id', $post->id)->where('user_id', Auth::user()->id)->first();
            if ($reaction) {
                if ($reaction->reaction !== $request->option) {
                    $reaction->reaction = $request->option;
                    $reaction->save();
                    return response()->json(['message' => 'Se actualizó tu voto.'], Response::HTTP_OK);
                } else {
                    return response()->json(['message' => 'Ya has votado con esta opción.'], Response::HTTP_OK);
                }
            } else {
                PostReactions::create([
                    'reaction' => $request->option,
                    'user_id' => Auth::user()->id,
                    'post_id' => $post->id,
                ]);
                return response()->json(['message' => 'Envíado.'], Response::HTTP_CREATED);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un error al momento de guardar tu reación, intentalo de nuevo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getPostReactions($slug)
    {
        try {
            $validator = Validator::make(['slug' => $slug], [
                'slug' => 'required|string',
            ], [
                'slug.required' => 'El slug es obligatorio',
                'slug.string' => 'El slug debe ser una cadena de texto',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }
            $post = Post::select('id')->where('slug', $slug)->first();
            $userHasVoted = PostReactions::where('post_id', $post->id)->where('user_id', auth::user()->id)->first();
            $reactions = PostReactions::select('reaction')->where('post_id', $post->id)->get();
            $likes = 0;
            $dislikes = 0;
            if (isset($reactions) && count($reactions) > 0) {
                $total_votos = count($reactions);
                foreach ($reactions as $reaction) {
                    if ($reaction->reaction == 'like') {
                        $likes++;
                    } else if ($reaction->reaction == 'dislike') {
                        $dislikes++;
                    }
                }
            }
            if ($likes > 0 || $dislikes > 0) {
                $dataToRetrieve = [
                    'likes' => ($likes / $total_votos) * 100,
                    'dislikes' => ($dislikes / $total_votos) * 100,
                    'total' => $total_votos,
                    'userHasVoted' => isset($userHasVoted) ? true : false,
                    'userVote' => isset($userHasVoted) ? $userHasVoted->reaction : null
                ];
                return response()->json(['results' => $dataToRetrieve], Response::HTTP_OK);
            } else {
                $dataToRetrieve = [
                    'likes' => 0,
                    'dislikes' => 0,
                    'total' => 0,
                    'userHasVoted' => false,
                    'userVote' => null
                ];
                return response()->json(['results' => $dataToRetrieve], Response::HTTP_OK);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Hubo un error al momento de obtener las reaciones, intentalo de nuevo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function createComment(Request $request)
    {
        try {
            $error_messages = [
                'article.required' => 'El slug es obligatorio',
                'article.string' => 'El slug debe ser una cadena de texto',
                'message.required' => 'El comentario es obligatorio',
                'message.string' => 'El comentario debe ser una cadena de texto',
                'message.max' => 'El comentario no debe exceder los 5000 caracteres',
            ];
            $validator = Validator::make($request->all(), [
                'article' => 'required|string',
                'message' => 'required|string|max:5000',
            ], $error_messages);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }
            $post = Post::where('slug', $request->article)->first();
            if (!$post) {
                return response()->json(['error' => 'El artículo no existe'], Response::HTTP_NOT_FOUND);
            }
            PostComments::create([
                'user_id' => Auth::user()->id,
                'post_id' => $post->id,
                'active' => true,
                'content' => $request->message,
                'active' => true,
            ]);
            return response()->json(['message' => 'Se guardó tu comentario'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un error al momento de crear el comentario, intentalo de nuevo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getComments($slug)
    {
        try {
            $post = Post::where('slug', $slug)->where('active', 1)->first();
            if (!$post) {
                return response()->json(['error' => 'El artículo no existe'], Response::HTTP_NOT_FOUND);
            }

            // Obtener los comentarios con los datos específicos
            $comments = PostComments::where('post_id', $post->id)
                ->where('active', true)
                ->with('user:id,name,parche,club') // Asegurarse de tener la relación `user` definida en el modelo PostComments
                ->get(['content', 'created_at', 'user_id', 'id'])
                ->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'club' => $comment->user->club,
                        'parche' => $comment->user->parche,
                        'user_name' => $comment->user->name,
                        'content' => $comment->content,
                        'created_at' => $comment->created_at,
                    ];
                });

            return response()->json(['comments' => $comments], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un error al momento de obtener los comentarios, intentalo de nuevo.' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function removeComment(Request $request)
    {
        try {
            $error_messages = [
                'id.required' => 'El id es obligatorio',
                'id.integer' => 'El id debe ser un numero',
            ];

            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
            ], $error_messages);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], Response::HTTP_BAD_REQUEST);
            }
            $id = $request->id;
            $comment = PostComments::where('id', $id)->where('user_id', Auth::user()->id)->first();
            if (!$comment) {
                return response()->json(['error' => 'El comentario no existe'], Response::HTTP_NOT_FOUND);
            }
            $comment->active = false;
            $comment->save();
            return response()->json(['message' => 'Comentario eliminado'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un error al momento de eliminar el comentario, intentalo de nuevo. ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
