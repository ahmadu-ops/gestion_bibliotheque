</main>
    
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-book"></i> Système de Gestion de Bibliothèque</h6>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">
                        <small>&copy; <?php echo date('Y'); ?> - Tous droits réservés</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Script spécifique à la page -->
    <?php if(isset($page_script)): ?>
        <script src="assets/js/<?php echo $page_script; ?>"></script>
    <?php endif; ?>
    
</body>
</html>