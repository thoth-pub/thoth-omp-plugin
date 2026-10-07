/**
 * Copyright (c) 2024-2026 Lepidus Tecnologia
 * Copyright (c) 2024-2026 Thoth
 * Distributed under the GNU GPL v3. See docs/COPYING.
 */
import {seedDraftBook, readRegisteredWork, readBookState} from '../../support/thoth';

describe('Thoth registration on publication', function () {
	beforeEach(function () {
		cy.server();
		seedDraftBook().as('book');
	});

	it('registers the monograph when publishing with Thoth selected', function () {
		readBookState(this.book.key).its('workId').should('be.null');
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit(`/index.php/publicknowledge/workflow/access/${this.book.submissionId}`);
		cy.get('#publication-button').click();
		cy.get('.pkpPublication').should('be.visible');

		// Publish through OMP with explicit consent to register in Thoth.
		cy.get('.pkpPublication').contains('button', /^\s*Publish\s*$/).click();
		cy.get('.pkpWorkflow__publishModal').should('be.visible').within(() => {
			cy.get('input[name="registerConfirmation"]').check();
			cy.get('select[name="thothImprintId"]').select('Cypress Imprint');
			cy.route('POST', `**/publications/${this.book.publicationId}/publish`).as('publish');
			cy.contains('button', /^\s*Publish\s*$/).click();
		});
		cy.wait('@publish').its('status').should('eq', 200);
		cy.get('.pkpWorkflow__publishModal').should('not.exist');
		// This workflow fetches the linked work status when the page opens.
		cy.get('.pkpPublication__thoth').contains('a', 'View').should('be.visible');
		cy.reload();
		cy.get('.pkpPublication__thoth')
			.contains('span', /^\s*Active\s*$/).scrollIntoView();
		cy.get('.pkpPublication__thoth')
			.contains('span', /^\s*Active\s*$/).should('be.visible');

		// The publish hook must persist the link, not merely report OMP publication success.
		readRegisteredWork(this.book.key).then((work) => {
			expect(work.title).to.eq(this.book.title);
			expect(work.imprintId).to.eq(this.book.imprintId);
			expect(work.workStatus).to.eq('ACTIVE');
		});
		readBookState(this.book.key).its('status').should('eq', 3);
	});
});
