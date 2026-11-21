import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'screens/login_screen.dart';

void main() {
  runApp(const PosCashierApp());
}

class PosCashierApp extends StatelessWidget {
  const PosCashierApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'POS Kasir Mobile',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF2563EB),
          primary: const Color(0xFF2563EB),
        ),
        useMaterial3: true,
        fontFamily: 'Roboto',
      ),
      home: const LoginScreen(),
    );
  }
}
