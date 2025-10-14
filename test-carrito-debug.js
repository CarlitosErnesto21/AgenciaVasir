// Script de test para debugging del carrito
console.log('🧪 Iniciando test de autenticación...');

// Test 1: Verificar autenticación
fetch('/api/test-auth', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
})
.then(response => response.json())
.then(data => {
    console.log('✅ Test de autenticación:', data);

    if (data.authenticated) {
        console.log('👤 Usuario autenticado:', data.user);

        // Test 2: Intentar crear venta con datos de prueba si está autenticado
        const testCarritoData = {
            productos: [
                {
                    id: 1,
                    cantidad: 1,
                    precio: 25.99,
                    nombre: "Producto Test"
                }
            ],
            customer_email: data.user.email
        };

        console.log('🛒 Probando creación de venta con:', testCarritoData);

        return fetch('/carrito/create-venta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(testCarritoData)
        });
    } else {
        console.log('❌ Usuario NO autenticado');
        return null;
    }
})
.then(response => {
    if (response) {
        console.log('📡 Status de create-venta:', response.status, response.statusText);
        return response.json();
    }
    return null;
})
.then(data => {
    if (data) {
        console.log('📦 Respuesta de create-venta:', data);
    }
})
.catch(error => {
    console.error('❌ Error en el test:', error);
});
