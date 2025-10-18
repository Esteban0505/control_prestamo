// Prueba funcional para amortización mixta
// Verifica que al seleccionar 'mixta', solo '15 Dias' esté habilitado en forma de pago
// Verifica cálculos siguen patrón alterno interés-capital
// Verifica tabla muestra solo pagos quincenales

describe('Prueba funcional de amortización mixta', () => {
    beforeEach(() => {
        // Visitar la página de edición de préstamos
        cy.visit('http://localhost/prestamo-1/admin/loans/edit');
    });

    it('Debe habilitar solo "15 Dias" cuando se selecciona amortización mixta', () => {
        // Seleccionar amortización mixta
        cy.get('#amortization_type').select('mixta');

        // Verificar que solo 'quincenal' esté habilitado
        cy.get('select[name="payment_m"] option').each(($option) => {
            if ($option.val() === 'quincenal') {
                cy.wrap($option).should('not.be.disabled');
                cy.wrap($option).should('be.selected');
            } else {
                cy.wrap($option).should('be.disabled');
            }
        });
    });

    it('Debe calcular correctamente el patrón alterno interés-capital para amortización mixta', () => {
        // Llenar formulario con datos de prueba
        cy.get('#cr_amount').clear().type('10000000'); // 10,000,000
        cy.get('#in_amount').clear().type('5'); // 5%
        cy.get('#fee').clear().type('12'); // 12 meses
        cy.get('#amortization_type').select('mixta');

        // Hacer clic en calcular
        cy.get('#calcular').click();

        // Verificar que se habilite el botón de ver amortización
        cy.get('#ver_amortizacion').should('not.be.disabled');

        // Hacer clic en ver amortización
        cy.get('#ver_amortizacion').click();

        // Verificar que el modal se abra
        cy.get('#amortizationModal').should('be.visible');

        // Verificar que la tabla tenga 24 filas (12 meses * 2 pagos por mes quincenal)
        cy.get('#preview_amortization_table tbody tr').should('have.length', 24);

        // Verificar patrón alterno: primer pago solo interés, segundo solo capital
        cy.get('#preview_amortization_table tbody tr').each(($row, index) => {
            const rowIndex = index + 1; // 1-based
            if (rowIndex % 2 === 1) {
                // Pagos impares: solo interés (capital = 0)
                cy.wrap($row).find('td').eq(3).should('contain', '0'); // Abono Capital
            } else {
                // Pagos pares: solo capital (interés = 0)
                cy.wrap($row).find('td').eq(4).should('contain', '0'); // Interés
            }
        });
    });

    it('Debe mostrar fechas quincenales en la tabla de amortización', () => {
        // Llenar formulario
        cy.get('#cr_amount').clear().type('10000000');
        cy.get('#in_amount').clear().type('5');
        cy.get('#fee').clear().type('12');
        cy.get('#amortization_type').select('mixta');
        cy.get('#date').clear().type('2024-01-01');

        cy.get('#calcular').click();
        cy.get('#ver_amortizacion').click();

        // Verificar fechas quincenales
        cy.get('#preview_amortization_table tbody tr').each(($row, index) => {
            const expectedDate = new Date('2024-01-01');
            expectedDate.setDate(expectedDate.getDate() + (index + 1) * 15);

            const formattedDate = expectedDate.toISOString().split('T')[0];
            cy.wrap($row).find('td').eq(1).should('contain', formattedDate);
        });
    });
});