import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // Ganti IP jika server diakses dari emulator (10.0.2.2) atau device fisik (192.168.1.10)
  static String baseUrl = 'http://192.168.1.10:8000/api';

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('pos_token');
  }

  static Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$baseUrl/login'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('pos_token', data['data']['token']);
      await prefs.setString('user_data', jsonEncode(data['data']['user']));
    }
    return data;
  }

  static Future<List<dynamic>> getProducts(int outletId, {String? search, int? categoryId}) async {
    final token = await getToken();
    var url = '$baseUrl/products?outlet_id=$outletId';
    if (search != null && search.isNotEmpty) url += '&search=$search';
    if (categoryId != null) url += '&category_id=$categoryId';

    final res = await http.get(
      Uri.parse(url),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );
    final data = jsonDecode(res.body);
    return data['data'] ?? [];
  }

  static Future<List<dynamic>> getCategories() async {
    final token = await getToken();
    final res = await http.get(
      Uri.parse('$baseUrl/categories'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );
    final data = jsonDecode(res.body);
    return data['data'] ?? [];
  }

  static Future<Map<String, dynamic>> checkout({
    required int outletId,
    required List<Map<String, dynamic>> items,
    required String paymentMethod,
    required double amountPaid,
    int? customerId,
    double taxPercentage = 11.0,
  }) async {
    final token = await getToken();
    final res = await http.post(
      Uri.parse('$baseUrl/pos/checkout'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({
        'outlet_id': outletId,
        'customer_id': customerId,
        'items': items,
        'payment_method': paymentMethod,
        'amount_paid': amountPaid,
        'tax_percentage': taxPercentage,
      }),
    );
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>?> getCurrentShift(int outletId) async {
    final token = await getToken();
    final res = await http.get(
      Uri.parse('$baseUrl/shifts/current?outlet_id=$outletId'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );
    final data = jsonDecode(res.body);
    return data['data'];
  }

  static Future<Map<String, dynamic>> openShift(int outletId, double initialCash) async {
    final token = await getToken();
    final res = await http.post(
      Uri.parse('$baseUrl/shifts/open'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'outlet_id': outletId, 'initial_cash': initialCash}),
    );
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> closeShift(int shiftId, double actualCash) async {
    final token = await getToken();
    final res = await http.post(
      Uri.parse('$baseUrl/shifts/$shiftId/close'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'Authorization': 'Bearer $token'},
      body: jsonEncode({'actual_cash': actualCash}),
    );
    return jsonDecode(res.body);
  }
}
