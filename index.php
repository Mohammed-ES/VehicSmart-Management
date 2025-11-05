<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VehicSmart - Professional Vehicle Management Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#1c1c1e',
                        'primary-light': '#f4f4f5',
                        'accent': '#ff7849',
                        'neutral': '#a1a1aa'
                    }
                }
            }
        }
    </script>
    <style>
        .scroll-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }
        /* Show first section immediately to avoid blank page */
        #home .scroll-reveal {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-white">
    <!-- Navigation -->
    <nav class="bg-primary-dark fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex-shrink-0">
                    <h1 class="text-white text-2xl font-bold">VehicSmart</h1>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="#about" class="text-white hover:text-accent px-3 py-2 rounded-md text-sm font-medium transition-colors">About</a>
                        <a href="#services" class="text-white hover:text-accent px-3 py-2 rounded-md text-sm font-medium transition-colors">Services</a>
                        <a href="#vehicles" class="text-white hover:text-accent px-3 py-2 rounded-md text-sm font-medium transition-colors">Vehicles</a>
                        <a href="#testimonials" class="text-white hover:text-accent px-3 py-2 rounded-md text-sm font-medium transition-colors">Testimonials</a>
                        <a href="#contact" class="text-white hover:text-accent px-3 py-2 rounded-md text-sm font-medium transition-colors">Contact</a>
                        <a href="auth/login.php" class="bg-accent text-white hover:bg-orange-600 px-4 py-2 rounded-md text-sm font-medium transition-colors">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="bg-primary-dark min-h-screen flex items-center pt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="scroll-reveal">
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    Revolutionize Your <span class="text-accent">Vehicle Management</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 mb-8 max-w-3xl mx-auto">
                    Smart, efficient, and comprehensive vehicle tracking and management solutions for modern businesses
                </p>
                <a href="auth/register.php" class="bg-accent hover:bg-orange-600 text-white font-bold py-4 px-8 rounded-lg text-lg transition-all duration-300 transform hover:scale-105 inline-block">
                    Get Started Today
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="bg-primary-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center scroll-reveal">
                <span class="inline-block px-4 py-1 rounded-full bg-accent/10 text-accent font-semibold text-sm mb-3">WHO WE ARE</span>
                <h2 class="text-3xl md:text-5xl font-bold text-primary-dark mb-8">About <span class="text-accent">VehicSmart</span></h2>
                <div class="w-24 h-1 bg-accent mx-auto mb-10"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-16">
                <div class="scroll-reveal">
                    <div class="relative">
                        <div class="bg-primary-dark h-64 md:h-96 rounded-lg overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1580273916550-e323be2ae537?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=400&q=80" 
                                 alt="Fleet Management Center" class="w-full h-full object-cover opacity-75">
                        </div>
                        <div class="absolute bottom-0 right-0 translate-x-4 translate-y-4 bg-accent py-3 px-6 rounded-md">
                            <p class="text-white font-bold text-xl">15+ Years Experience</p>
                        </div>
                    </div>
                </div>
                
                <div class="scroll-reveal">
                    <h3 class="text-2xl font-bold text-primary-dark mb-6">Revolutionizing Fleet Management Since 2010</h3>
                    <div class="w-16 h-1 bg-accent mb-6"></div>
                    <p class="text-lg text-gray-700 mb-6">
                        At VehicSmart, we believe that effective vehicle management should be simple, intelligent, and accessible to businesses of all sizes. Our mission is to transform how organizations track, maintain, and optimize their vehicle fleets through cutting-edge technology and user-friendly interfaces.
                    </p>
                    <p class="text-lg text-gray-700 mb-6">
                        Founded by industry experts with decades of experience in logistics and technology, VehicSmart combines deep domain knowledge with innovative solutions to deliver unparalleled value to our clients.
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-8">
                        <div class="flex items-center">
                            <div class="bg-accent/10 p-3 rounded-md mr-3">
                                <span class="text-accent font-bold text-2xl">98%</span>
                            </div>
                            <p class="font-medium">Customer Satisfaction Rate</p>
                        </div>
                        <div class="flex items-center">
                            <div class="bg-accent/10 p-3 rounded-md mr-3">
                                <span class="text-accent font-bold text-2xl">25k+</span>
                            </div>
                            <p class="font-medium">Vehicles Managed</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="max-w-4xl mx-auto text-center scroll-reveal">
                <p class="text-lg text-gray-700 italic">
                    "We're committed to helping businesses reduce costs, improve efficiency, and ensure the safety and reliability of their vehicle operations through data-driven insights and proactive management tools."
                </p>
                <p class="font-semibold text-primary-dark mt-4">- Michael Richards, Founder & CEO</p>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center scroll-reveal">
                <span class="inline-block px-4 py-1 rounded-full bg-accent/10 text-accent font-semibold text-sm mb-3">WHAT WE OFFER</span>
                <h2 class="text-3xl md:text-5xl font-bold text-primary-dark mb-6">Our <span class="text-accent">Services</span></h2>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-10">
                    Comprehensive solutions tailored to optimize your vehicle fleet operations, reduce costs, and enhance efficiency
                </p>
                <div class="w-24 h-1 bg-accent mx-auto mb-16"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative overflow-hidden">
                        <div class="bg-accent w-16 h-16 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-primary-dark mb-4 group-hover:text-accent transition-colors">Real-Time Vehicle Tracking</h3>
                        <p class="text-gray-700">Monitor your entire fleet with GPS precision. Get instant location updates, route optimization, and driver behavior analytics to maximize efficiency.</p>
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative overflow-hidden">
                        <div class="bg-accent w-16 h-16 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-primary-dark mb-4 group-hover:text-accent transition-colors">Maintenance Management</h3>
                        <p class="text-gray-700">Never miss critical maintenance with automated scheduling and alerts. Prevent breakdowns and extend vehicle life with predictive maintenance insights.</p>
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative overflow-hidden">
                        <div class="bg-accent w-16 h-16 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-primary-dark mb-4 group-hover:text-accent transition-colors">Fuel Monitoring</h3>
                        <p class="text-gray-700">Track fuel consumption patterns, identify inefficiencies, and reduce costs with comprehensive fuel management and reporting tools.</p>
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative overflow-hidden">
                        <div class="bg-accent w-16 h-16 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-primary-dark mb-4 group-hover:text-accent transition-colors">Driver Management</h3>
                        <p class="text-gray-700">Manage driver profiles, track performance metrics, monitor driving behavior, and ensure compliance with safety regulations.</p>
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative overflow-hidden">
                        <div class="bg-accent w-16 h-16 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-primary-dark mb-4 group-hover:text-accent transition-colors">Fleet Analytics</h3>
                        <p class="text-gray-700">Make data-driven decisions with comprehensive reporting and analytics. Get insights into utilization, costs, and performance metrics.</p>
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative overflow-hidden">
                        <div class="bg-accent w-16 h-16 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-primary-dark mb-4 group-hover:text-accent transition-colors">Compliance Management</h3>
                        <p class="text-gray-700">Stay compliant with industry regulations and safety standards. Automated documentation and reporting for audits and inspections.</p>
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <!-- Vehicles Section -->
    <section id="vehicles" class="bg-primary-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center scroll-reveal">
                <span class="inline-block px-4 py-1 rounded-full bg-accent/10 text-accent font-semibold text-sm mb-3">OUR FLEET</span>
                <h2 class="text-3xl md:text-5xl font-bold text-primary-dark mb-6">Vehicle <span class="text-accent">Portfolio</span></h2>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-10">
                    Discover our diverse fleet of professional vehicles managed by VehicSmart's advanced tracking system
                </p>
                <div class="w-24 h-1 bg-accent mx-auto mb-16"></div>
            </div>
            
            <!-- Vehicle Type Navigation -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="scroll-reveal group">
                    <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 h-full">
                        <div class="relative overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1629421865882-d64347f3961c?q=80&w=387&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Blue Car" class="w-full h-60 object-cover transition-transform duration-500 group-hover:scale-110">
                            <div class="absolute top-4 right-4 bg-accent text-white text-xs font-bold uppercase py-1 px-2 rounded-md">
                                Premium
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <div class="bg-accent/10 p-2 rounded-full mr-3">
                                </div>
                                <h3 class="text-xl font-bold text-primary-dark">Luxury Cars</h3>
                            </div>
                            <p class="text-gray-700 text-sm mb-4">Premium vehicles for executive transport and high-end client services</p>
                            
                            <div class="border-t border-gray-100 pt-4">
                                <div class="flex justify-between text-sm">
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        24/7 Tracking
                                    </div>
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        GPS Enabled
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 h-full">
                        <div class="relative overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1586191552066-d52dd1e3af86?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&h=350&q=80" alt="Yellow Truck" class="w-full h-60 object-cover transition-transform duration-500 group-hover:scale-110">
                            <div class="absolute top-4 right-4 bg-accent text-white text-xs font-bold uppercase py-1 px-2 rounded-md">
                                Heavy Duty
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <div class="bg-accent/10 p-2 rounded-full mr-3">
                                </div>
                                <h3 class="text-xl font-bold text-primary-dark">Transport Trucks</h3>
                            </div>
                            <p class="text-gray-700 text-sm mb-4">Robust logistics solutions for nationwide goods transportation</p>
                            
                            <div class="border-t border-gray-100 pt-4">
                                <div class="flex justify-between text-sm">
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        24/7 Tracking
                                    </div>
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        GPS Enabled
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 h-full">
                        <div class="relative overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1570125909232-eb263c188f7e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&h=350&q=80" alt="Modern Bus" class="w-full h-60 object-cover transition-transform duration-500 group-hover:scale-110">
                            <div class="absolute top-4 right-4 bg-accent text-white text-xs font-bold uppercase py-1 px-2 rounded-md">
                                Passenger
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <div class="bg-accent/10 p-2 rounded-full mr-3">
                                </div>
                                <h3 class="text-xl font-bold text-primary-dark">Transport Buses</h3>
                            </div>
                            <p class="text-gray-700 text-sm mb-4">Comfortable and secure transportation for passengers</p>
                            
                            <div class="border-t border-gray-100 pt-4">
                                <div class="flex justify-between text-sm">
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        24/7 Tracking
                                    </div>
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        GPS Enabled
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <div class="scroll-reveal group">
                    <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 hover:scale-105 h-full">
                        <div class="relative overflow-hidden">
                            <img src="https://images.unsplash.com/photo-1530267981375-f0de937f5f13?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&h=350&q=80" alt="Green Tractor" class="w-full h-60 object-cover transition-transform duration-500 group-hover:scale-110">
                            <div class="absolute top-4 right-4 bg-accent text-white text-xs font-bold uppercase py-1 px-2 rounded-md">
                                Agricultural
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <div class="bg-accent/10 p-2 rounded-full mr-3">
                                </div>
                                <h3 class="text-xl font-bold text-primary-dark">Agricultural Tractors</h3>
                            </div>
                            <p class="text-gray-700 text-sm mb-4">Powerful equipment for modern agriculture and field operations</p>
                            
                            <div class="border-t border-gray-100 pt-4">
                                <div class="flex justify-between text-sm">
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        24/7 Tracking
                                    </div>
                                    <div class="flex items-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        GPS Enabled
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center scroll-reveal">
                <span class="inline-block px-4 py-1 rounded-full bg-accent/10 text-accent font-semibold text-sm mb-3">SUCCESS STORIES</span>
                <h2 class="text-3xl md:text-5xl font-bold text-primary-dark mb-6">What Our <span class="text-accent">Clients Say</span></h2>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-10">
                    Hear from businesses that have transformed their fleet management with VehicSmart
                </p>
                <div class="w-24 h-1 bg-accent mx-auto mb-16"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative h-full">
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                        <div class="mb-6">
                            <div class="flex mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-8 italic relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-accent/20 absolute -top-4 -left-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.625 2.125a1 1 0 0 1 1.75 0l1.038 1.787a1 1 0 0 0 .852.493h2.072a1 1 0 0 1 .553 1.85l-1.676 1.124a1 1 0 0 0-.363 1.118l.638 1.965a1 1 0 0 1-1.536 1.117l-1.677-1.125a1 1 0 0 0-1.103 0l-1.676 1.125a1 1 0 0 1-1.536-1.117l.638-1.965a1 1 0 0 0-.363-1.118L2.110 6.255a1 1 0 0 1 .553-1.85h2.072a1 1 0 0 0 .852-.493l1.038-1.787zM1.25 14a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5H2a.75.75 0 0 1-.75-.75zM3.25 18a.75.75 0 0 1 .75-.75h8a.75.75 0 0 1 0 1.5H4a.75.75 0 0 1-.75-.75z" />
                            </svg>
                            "VehicSmart has transformed our fleet operations completely. The real-time tracking and maintenance alerts have saved us thousands in unexpected repairs and improved our delivery efficiency by 35%."
                        </p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-accent/10 rounded-full overflow-hidden flex items-center justify-center mr-4">
                                <span class="text-accent font-bold text-xl">SJ</span>
                            </div>
                            <div>
                                <p class="font-semibold text-primary-dark">Sarah Johnson</p>
                                <p class="text-neutral text-sm">Operations Manager, LogiCorp</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative h-full">
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                        <div class="mb-6">
                            <div class="flex mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-8 italic relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-accent/20 absolute -top-4 -left-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.625 2.125a1 1 0 0 1 1.75 0l1.038 1.787a1 1 0 0 0 .852.493h2.072a1 1 0 0 1 .553 1.85l-1.676 1.124a1 1 0 0 0-.363 1.118l.638 1.965a1 1 0 0 1-1.536 1.117l-1.677-1.125a1 1 0 0 0-1.103 0l-1.676 1.125a1 1 0 0 1-1.536-1.117l.638-1.965a1 1 0 0 0-.363-1.118L2.110 6.255a1 1 0 0 1 .553-1.85h2.072a1 1 0 0 0 .852-.493l1.038-1.787zM1.25 14a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5H2a.75.75 0 0 1-.75-.75zM3.25 18a.75.75 0 0 1 .75-.75h8a.75.75 0 0 1 0 1.5H4a.75.75 0 0 1-.75-.75z" />
                            </svg>
                            "The fuel monitoring system alone has helped us reduce our fuel costs by 20%. The detailed analytics and reporting features give us insights we never had before. Highly recommended!"
                        </p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-accent/10 rounded-full overflow-hidden flex items-center justify-center mr-4">
                                <span class="text-accent font-bold text-xl">MC</span>
                            </div>
                            <div>
                                <p class="font-semibold text-primary-dark">Michael Chen</p>
                                <p class="text-neutral text-sm">Fleet Director, TransGlobal Solutions</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="scroll-reveal group">
                    <div class="bg-primary-light p-8 rounded-lg hover:shadow-xl transition-all duration-300 border-b-4 border-transparent hover:border-accent relative h-full">
                        <div class="absolute top-0 right-0 bg-accent/10 w-24 h-24 rounded-full -m-12 opacity-20"></div>
                        <div class="mb-6">
                            <div class="flex mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-accent" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-8 italic relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-accent/20 absolute -top-4 -left-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.625 2.125a1 1 0 0 1 1.75 0l1.038 1.787a1 1 0 0 0 .852.493h2.072a1 1 0 0 1 .553 1.85l-1.676 1.124a1 1 0 0 0-.363 1.118l.638 1.965a1 1 0 0 1-1.536 1.117l-1.677-1.125a1 1 0 0 0-1.103 0l-1.676 1.125a1 1 0 0 1-1.536-1.117l.638-1.965a1 1 0 0 0-.363-1.118L2.110 6.255a1 1 0 0 1 .553-1.85h2.072a1 1 0 0 0 .852-.493l1.038-1.787zM1.25 14a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 0 1.5H2a.75.75 0 0 1-.75-.75zM3.25 18a.75.75 0 0 1 .75-.75h8a.75.75 0 0 1 0 1.5H4a.75.75 0 0 1-.75-.75z" />
                            </svg>
                            "Implementation was seamless and the support team is exceptional. VehicSmart has made vehicle management effortless for our growing business. The ROI was evident within the first quarter."
                        </p>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-accent/10 rounded-full overflow-hidden flex items-center justify-center mr-4">
                                <span class="text-accent font-bold text-xl">ER</span>
                            </div>
                            <div>
                                <p class="font-semibold text-primary-dark">Emma Rodriguez</p>
                                <p class="text-neutral text-sm">CEO, Urban Delivery Services</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-16 text-center scroll-reveal">
                <a href="#contact" class="inline-flex items-center bg-accent hover:bg-accent/90 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-300 group">
                    Read More Success Stories
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="bg-primary-light py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center scroll-reveal">
                <span class="inline-block px-4 py-1 rounded-full bg-accent/10 text-accent font-semibold text-sm mb-3">CONTACT US</span>
                <h2 class="text-3xl md:text-5xl font-bold text-primary-dark mb-6">Get In <span class="text-accent">Touch</span></h2>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-10">
                    Have questions about our vehicle management platform? Ready to transform your fleet operations? We're here to help!
                </p>
                <div class="w-24 h-1 bg-accent mx-auto mb-16"></div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-12">
                <!-- Contact Information -->
                <div class="scroll-reveal lg:col-span-2">
                    <div class="bg-white p-8 rounded-lg shadow-lg h-full relative overflow-hidden">
                        <div class="absolute top-0 right-0 bg-accent/10 w-48 h-48 rounded-full -m-24 opacity-20"></div>
                        
                        <h3 class="text-2xl font-bold text-primary-dark mb-8 border-b border-gray-200 pb-4">How To Reach Us</h3>
                        
                        <div class="space-y-6">
                            <div class="flex items-start">
                                <div class="bg-accent/10 p-3 rounded-full mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-primary-dark">Visit Our Office</h4>
                                    <p class="text-gray-700 mt-1">123 Business Avenue</p>
                                    <p class="text-gray-700">Tech District, TD 12345</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="bg-accent/10 p-3 rounded-full mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-primary-dark">Call Us</h4>
                                    <p class="text-gray-700 mt-1">Sales: (555) 123-4567</p>
                                    <p class="text-gray-700">Support: (555) 987-6543</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="bg-accent/10 p-3 rounded-full mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-primary-dark">Email Us</h4>
                                    <p class="text-gray-700 mt-1">info@vehicsmart.com</p>
                                    <p class="text-gray-700">support@vehicsmart.com</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="bg-accent/10 p-3 rounded-full mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-primary-dark">Business Hours</h4>
                                    <p class="text-gray-700 mt-1">Monday - Friday: 9AM - 5PM</p>
                                    <p class="text-gray-700">Weekend: Closed</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="font-semibold text-primary-dark mb-4">Connect With Us</h4>
                            <div class="flex space-x-4">
                                <a href="#" class="bg-accent/10 p-2 rounded-full hover:bg-accent/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-accent" viewBox="0 0 16 16">
                                        <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
                                    </svg>
                                </a>
                                <a href="#" class="bg-accent/10 p-2 rounded-full hover:bg-accent/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-accent" viewBox="0 0 16 16">
                                        <path d="M5.026 15c6.038 0 9.341-5.003 9.341-9.334 0-.14 0-.282-.006-.422A6.685 6.685 0 0 0 16 3.542a6.658 6.658 0 0 1-1.889.518 3.301 3.301 0 0 0 1.447-1.817 6.533 6.533 0 0 1-2.087.793A3.286 3.286 0 0 0 7.875 6.03a9.325 9.325 0 0 1-6.767-3.429 3.289 3.289 0 0 0 1.018 4.382A3.323 3.323 0 0 1 .64 6.575v.045a3.288 3.288 0 0 0 2.632 3.218 3.203 3.203 0 0 1-.865.115 3.23 3.23 0 0 1-.614-.057 3.283 3.283 0 0 0 3.067 2.277A6.588 6.588 0 0 1 .78 13.58a6.32 6.32 0 0 1-.78-.045A9.344 9.344 0 0 0 5.026 15z"/>
                                    </svg>
                                </a>
                                <a href="#" class="bg-accent/10 p-2 rounded-full hover:bg-accent/20 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="text-accent" viewBox="0 0 16 16">
                                        <path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854V1.146zm4.943 12.248V6.169H2.542v7.225h2.401zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248-.822 0-1.359.54-1.359 1.248 0 .694.521 1.248 1.327 1.248h.016zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016a5.54 5.54 0 0 1 .016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225h2.4z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="scroll-reveal lg:col-span-3">
                    <form id="contactForm" class="bg-white p-8 rounded-lg shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 bg-accent/10 w-48 h-48 rounded-full -m-24 opacity-20"></div>
                        
                        <h3 class="text-2xl font-bold text-primary-dark mb-8 border-b border-gray-200 pb-4">Send Us a Message</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-primary-dark mb-2">Full Name*</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input type="text" id="name" name="name" required class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent transition-colors">
                                </div>
                                <span id="nameError" class="text-red-500 text-sm hidden">Please enter your full name</span>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-primary-dark mb-2">Email Address*</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                        </svg>
                                    </div>
                                    <input type="email" id="email" name="email" required class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent transition-colors">
                                </div>
                                <span id="emailError" class="text-red-500 text-sm hidden">Please enter a valid email address</span>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="subject" class="block text-sm font-medium text-primary-dark mb-2">Subject*</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 3a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2H5zm0 2h10v7h-2l-1 2H8l-1-2H5V5z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" id="subject" name="subject" required class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent transition-colors">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="message" class="block text-sm font-medium text-primary-dark mb-2">Message*</label>
                            <div class="relative">
                                <div class="absolute top-3 left-3 pointer-events-none">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM7 8H5v2h2V8zm2 0h2v2H9V8zm6 0h-2v2h2V8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <textarea id="message" name="message" rows="5" required class="w-full pl-10 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent transition-colors"></textarea>
                            </div>
                            <span id="messageError" class="text-red-500 text-sm hidden">Please enter your message</span>
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input id="privacy" name="privacy" type="checkbox" class="h-4 w-4 text-accent border-gray-300 rounded focus:ring-accent">
                            <label for="privacy" class="ml-2 block text-sm text-gray-700">
                                I agree to the <a href="#" class="text-accent hover:underline">Privacy Policy</a> and <a href="#" class="text-accent hover:underline">Terms of Service</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-accent hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-md transition-all duration-300 transform hover:scale-105 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                            Send Message
                        </button>
                        
                        <div id="formSuccess" class="text-green-600 text-center mt-4 hidden">
                            Thank you! Your message has been sent successfully. We'll get back to you shortly.
                        </div>
                    </form>
                    
                    <!-- Map -->
                    <div class="mt-6 rounded-lg overflow-hidden shadow-lg">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387190.2799160891!2d-74.25987368715491!3d40.69767006458873!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2sus!4v1642000000000!5m2!1sen!2sus" 
                            width="100%" 
                            height="250" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-primary-dark py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-white mb-4">VehicSmart</h3>
                <p class="text-gray-300 mb-6">Professional Vehicle Management Solutions</p>
                <div class="border-t border-gray-700 pt-6">
                    <p class="text-gray-400 text-sm">
                        &copy; <?php echo date('Y'); ?> VehicSmart. All rights reserved. | Privacy Policy | Terms of Service
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- TypeScript will be compiled to JavaScript -->
    <script src="app.js"></script>
</body>
</html>
