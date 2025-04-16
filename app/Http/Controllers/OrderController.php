<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Afficher la liste des commandes pour l'utilisateur connecté
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Order::query();

        // Si l'utilisateur est un client, filtrer ses commandes
        if ($user->role === 'client') {
            $query->where('client_id', $user->id);
        }

        // Si l'utilisateur est un distributeur, filtrer les commandes qui lui sont assignées
        if ($user->role === 'distributor') {
            $query->where('distributor_id', $user->id);
        }

        // Filtrage par statut si spécifié
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filtrage par date si spécifié
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('collection_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('collection_date', '<=', $request->to_date);
        }

        // Recherche par numéro de commande ou nom du client
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->search . '%');
            });
        }

        // Tri
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $orders = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_description' => 'required|string|max:255',
            'package_designation' => 'required|string|max:255',
            'package_number' => 'required|integer',
            'package_dimensions' => 'required|string|max:100',
            'package_weight' => 'required|numeric',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string',
            'collection_date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        $order = new Order();
        $order->client_id = $user->id;
        $order->order_number = 'ORD-' . time() . '-' . $user->id;
        $order->package_description = $request->package_description;
        $order->package_designation = $request->package_designation;
        $order->package_number = $request->package_number;
        $order->package_dimensions = $request->package_dimensions;
        $order->package_weight = $request->package_weight;
        $order->customer_name = $request->customer_name;
        $order->customer_phone = $request->customer_phone;
        $order->delivery_address = $request->delivery_address;
        $order->collection_date = $request->collection_date;
        $order->status = 'pending';
        $order->save();

        // Auto-assignation à un distributeur basée sur la zone géographique (à implémenter)
        // $this->assignDistributor($order);

        return response()->json([
            'success' => true,
            'message' => 'Commande créée avec succès',
            'data' => $order
        ], 201);
    }

    /**
     * Afficher les détails d'une commande spécifique
     */
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::findOrFail($id);

        // Vérification des permissions
        if ($user->role === 'client' && $order->client_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à cette commande'
            ], 403);
        }

        if ($user->role === 'distributor' && $order->distributor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à cette commande'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Mettre à jour une commande
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $order = Order::findOrFail($id);

        // Vérification des permissions
        if ($user->role === 'client' && $order->client_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à cette commande'
            ], 403);
        }

        // Seul un admin peut modifier une commande déjà en cours de livraison
        if ($user->role !== 'admin' && in_array($order->status, ['in_transit', 'delivered', 'failed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier une commande en cours de livraison'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'package_description' => 'string|max:255',
            'package_designation' => 'string|max:255',
            'package_number' => 'integer',
            'package_dimensions' => 'string|max:100',
            'package_weight' => 'numeric',
            'customer_name' => 'string|max:255',
            'customer_phone' => 'string|max:20',
            'delivery_address' => 'string',
            'collection_date' => 'date|after_or_equal:today',
            'status' => 'string|in:pending,confirmed,in_transit,delivered,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update uniquement les champs fournis
        $fillableFields = [
            'package_description', 'package_designation', 'package_number',
            'package_dimensions', 'package_weight', 'customer_name',
            'customer_phone', 'delivery_address', 'collection_date'
        ];

        foreach ($fillableFields as $field) {
            if ($request->has($field)) {
                $order->$field = $request->$field;
            }
        }

        // Seul un admin ou distributeur peut changer le statut
        if (in_array($user->role, ['admin', 'distributor']) && $request->has('status')) {
            $order->status = $request->status;

            // Si le statut est "failed", on doit enregistrer la raison
            if ($request->status === 'failed' && $request->has('failure_reason')) {
                $order->failure_reason = $request->failure_reason;
            }
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Commande mise à jour avec succès',
            'data' => $order
        ]);
    }

    /**
     * Supprimer une commande
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $order = Order::findOrFail($id);

        // Seul un admin peut supprimer n'importe quelle commande
        if ($user->role !== 'admin') {
            // Un client ne peut supprimer que ses propres commandes en statut "pending"
            if ($user->role === 'client' && ($order->client_id !== $user->id || $order->status !== 'pending')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé pour supprimer cette commande'
                ], 403);
            }
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Commande supprimée avec succès'
        ]);
    }

    /**
     * Générer le document de livraison
     */
    public function generateDeliveryDocument($id)
    {
        $user = Auth::user();
        $order = Order::findOrFail($id);

        // Vérification des permissions
        if ($user->role === 'client' && $order->client_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé à cette commande'
            ], 403);
        }

        // Code pour générer le PDF ici (utilisant une bibliothèque comme DOMPDF)
        // Pour cet exemple, nous retournons simplement une réponse de succès

        return response()->json([
            'success' => true,
            'message' => 'Document de livraison généré avec succès',
            'document_url' => url('/api/documents/order-' . $order->id . '.pdf')
        ]);
    }

    /**
     * Mise à jour du statut d'une commande par un distributeur
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $order = Order::findOrFail($id);

        // Vérification que l'utilisateur est un distributeur assigné à cette commande
        if ($user->role !== 'distributor' || $order->distributor_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé pour mettre à jour cette commande'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:confirmed,in_transit,delivered,failed',
            'failure_reason' => 'required_if:status,failed|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order->status = $request->status;

        if ($request->status === 'failed') {
            $order->failure_reason = $request->failure_reason;
        }

        if ($request->status === 'delivered') {
            $order->delivered_at = now();
        }

        $order->save();

        // Envoyer une notification au client (à implémenter)
        // $this->notifyClient($order);

        return response()->json([
            'success' => true,
            'message' => 'Statut de la commande mis à jour avec succès',
            'data' => $order
        ]);
    }

    /**
     * Réassigner une commande à un autre distributeur (admin uniquement)
     */
    public function reassignOrder(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Seul un administrateur peut réassigner une commande'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'distributor_id' => 'required|exists:users,id,role,distributor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($id);
        $order->distributor_id = $request->distributor_id;
        $order->save();

        // Notifier le nouveau distributeur (à implémenter)
        // $this->notifyDistributor($order);

        return response()->json([
            'success' => true,
            'message' => 'Commande réassignée avec succès',
            'data' => $order
        ]);
    }
}
