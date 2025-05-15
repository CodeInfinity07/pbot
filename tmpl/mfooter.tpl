        </main>
         </div>
    </div>

    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript to handle active state
        document.addEventListener('DOMContentLoaded', function() {
            var path = window.location.pathname;
            var page = path.split("/").pop();
            var links = document.querySelectorAll('.sidebar .nav-link');
            links.forEach(function(link) {
                if(link.getAttribute('href') === page) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>