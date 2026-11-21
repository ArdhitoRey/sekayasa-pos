import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';

class PosScreen extends StatefulWidget {
  final Map<String, dynamic> userData;
  const PosScreen({super.key, required this.userData});

  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> {
  final currencyFmt = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  List<dynamic> _products = [];
  List<dynamic> _categories = [];
  int? _selectedCategory;
  final Map<int, int> _cart = {}; // productId -> qty
  Map<String, dynamic>? _activeShift;
  bool _isLoading = true;
  String _search = '';

  int get _outletId => widget.userData['outlet_id'] ?? 1;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final prods = await ApiService.getProducts(_outletId, search: _search, categoryId: _selectedCategory);
      final cats = await ApiService.getCategories();
      final shift = await ApiService.getCurrentShift(_outletId);
      setState(() {
        _products = prods;
        _categories = cats;
        _activeShift = shift;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  void _addToCart(dynamic product) {
    final id = product['id'] as int;
    final stock = product['current_stock'] as int;
    final currentQty = _cart[id] ?? 0;

    if (currentQty >= stock) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Stok tidak mencukupi')),
      );
      return;
    }

    setState(() {
      _cart[id] = currentQty + 1;
    });
  }

  double get _subtotal {
    double total = 0;
    _cart.forEach((id, qty) {
      final p = _products.firstWhere((it) => it['id'] == id, orElse: () => null);
      if (p != null) {
        total += (double.tryParse(p['sell_price'].toString()) ?? 0) * qty;
      }
    });
    return total;
  }

  double get _tax => _subtotal * 0.11;
  double get _grandTotal => _subtotal + _tax;

  void _showCheckoutDialog() {
    if (_cart.isEmpty) return;
    double cashPaid = _grandTotal;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) {
        return StatefulBuilder(builder: (ctx, setModalState) {
          final change = cashPaid - _grandTotal;
          return Padding(
            padding: EdgeInsets.only(
              left: 20, right: 20, top: 20,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.between,
                  children: [
                    const Text('Checkout & Bayar', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(ctx)),
                  ],
                ),
                const Divider(),
                Text('Total Belanja: ${currencyFmt.format(_grandTotal)}', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.blue)),
                const SizedBox(height: 12),
                const Text('Uang Pembayaran Tunai (Rp):', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                TextField(
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(border: OutlineInputBorder(), prefixText: 'Rp '),
                  controller: TextEditingController(text: cashPaid.toInt().toString()),
                  onChanged: (val) {
                    setModalState(() {
                      cashPaid = double.tryParse(val) ?? 0;
                    });
                  },
                ),
                const SizedBox(height: 8),
                Text(
                  change >= 0 ? 'Kembalian: ${currencyFmt.format(change)}' : 'Uang Kurang!',
                  style: TextStyle(fontWeight: FontWeight.bold, color: change >= 0 ? Colors.green : Colors.red),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: change < 0 ? null : () async {
                    Navigator.pop(ctx);
                    await _processPayment(cashPaid);
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.green,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                  ),
                  child: const Text('Selesaikan Transaksi & Cetak Struk', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          );
        });
      },
    );
  }

  Future<void> _processPayment(double paid) async {
    final items = _cart.entries.map((e) => {'product_id': e.key, 'quantity': e.value}).toList();
    final res = await ApiService.checkout(
      outletId: _outletId,
      items: items,
      paymentMethod: 'cash',
      amountPaid: paid,
    );

    if (res['status'] == 'success') {
      setState(() => _cart.clear());
      _loadData();
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text('Transaksi Berhasil!'),
          content: Text('No Struk: ${res['data']['transaction']['invoice_number']}\nTotal: ${currencyFmt.format(res['data']['receipt']['total_amount'])}\nKembalian: ${currencyFmt.format(res['data']['receipt']['change_amount'])}'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('Selesai')),
          ],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.userData['name'] ?? 'Kasir', style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold)),
            Text('Shift: ${_activeShift != null ? "Aktif (Modal Rp ${_activeShift!['initial_cash']})" : "Tutup"}', style: const TextStyle(fontSize: 11)),
          ],
        ),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: _loadData),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                // Search Bar
                Padding(
                  padding: const EdgeInsets.all(8.0),
                  child: TextField(
                    decoration: InputDecoration(
                      hintText: 'Cari produk / barcode...',
                      prefixIcon: const Icon(Icons.search),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(vertical: 8),
                    ),
                    onChanged: (val) {
                      _search = val;
                      _loadData();
                    },
                  ),
                ),
                // Products Grid
                Expanded(
                  child: GridView.builder(
                    padding: const EdgeInsets.all(8),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 1.1,
                      crossAxisSpacing: 8,
                      mainAxisSpacing: 8,
                    ),
                    itemCount: _products.length,
                    itemBuilder: (ctx, i) {
                      final p = _products[i];
                      final stock = p['current_stock'] as int;
                      final inCart = _cart[p['id']] ?? 0;
                      return InkWell(
                        onTap: () => _addToCart(p),
                        child: Card(
                          elevation: 2,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          child: Padding(
                            padding: const EdgeInsets.all(10),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(p['name'], style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold), maxLines: 2),
                                    Text('Stok: $stock', style: TextStyle(fontSize: 10, color: stock > 0 ? Colors.green : Colors.red)),
                                  ],
                                ),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.between,
                                  children: [
                                    Text(currencyFmt.format(double.tryParse(p['sell_price'].toString()) ?? 0), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.blue)),
                                    if (inCart > 0)
                                      CircleAvatar(radius: 10, backgroundColor: Colors.blue, child: Text('$inCart', style: const TextStyle(fontSize: 10, color: Colors.white))),
                                  ],
                                )
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
                // Bottom Checkout Bar
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)]),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.between,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${_cart.values.fold(0, (a, b) => a + b)} Item di Keranjang', style: const TextStyle(fontSize: 11, color: Colors.grey)),
                          Text(currencyFmt.format(_grandTotal), style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.blue)),
                        ],
                      ),
                      ElevatedButton(
                        onPressed: _cart.isEmpty ? null : _showCheckoutDialog,
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.green, foregroundColor: Colors.white),
                        child: const Text('Bayar (F4)'),
                      ),
                    ],
                  ),
                )
              ],
            ),
    );
  }
}
