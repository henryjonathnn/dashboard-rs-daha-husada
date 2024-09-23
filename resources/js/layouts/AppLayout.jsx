import React, { lazy, Suspense, useEffect } from 'react';
import { ErrorBoundary } from 'react-error-boundary';
import useResponsive from '@/hooks/useResponsive';
import Loading from '@/components/ui/Loading';

const Sidebar = lazy(() => import('@/layouts/Sidebar'));
const Navbar = lazy(() => import('@/layouts/Navbar'));
const Footer = lazy(() => import('@/layouts/Footer'));

const ErrorFallback = ({ error }) => (
  <div role="alert" className="p-4 bg-red-100 border border-red-400 text-red-700 rounded">
    <h2 className="text-lg font-semibold mb-2">Oops! Something went wrong</h2>
    <p className="mb-2">We're sorry, but an error occurred. Please try refreshing the page or contact support if the problem persists.</p>
    <details className="whitespace-pre-wrap">
      <summary>Error details</summary>
      {error.message}
    </details>
  </div>
);

const AppLayout = ({ children, title }) => {
  const { isMobile, isTablet, collapsed, setCollapsed } = useResponsive();

  const layoutStyle = {
    marginLeft: !isMobile && !isTablet ? (collapsed ? '4rem' : '16rem') : '0',
  };

  useEffect(() => {
    // Set interval to refresh the page every 1 minute (60,000 ms)
    const interval = setInterval(() => {
      window.location.reload();
    }, 60000); // 1 minute in milliseconds

    // Cleanup interval on component unmount
    return () => clearInterval(interval);
  }, []);

  return (
    <ErrorBoundary FallbackComponent={ErrorFallback}>
      <div className="flex flex-col min-h-screen bg-soft-green">
        {(isMobile || isTablet) && (
          <Suspense fallback={<Loading />}>
            <Navbar title={title} />
          </Suspense>
        )}
        <div className="flex flex-1">
          {!isMobile && !isTablet && (
            <Suspense fallback={<Loading />}>
              <Sidebar collapsed={collapsed} setCollapsed={setCollapsed} />
            </Suspense>
          )}
          <main
            className="flex-1 transition-all duration-300 ease-in-out overflow-y-auto"
            style={layoutStyle}
          >
            <div className="container mx-auto px-4 py-6">
              <Suspense fallback={<Loading />}>
                {children}
              </Suspense>
            </div>
            <Suspense fallback={<Loading />}>
              <Footer />
            </Suspense>
          </main>
        </div>
      </div>
    </ErrorBoundary>
  );
};

export default AppLayout;
