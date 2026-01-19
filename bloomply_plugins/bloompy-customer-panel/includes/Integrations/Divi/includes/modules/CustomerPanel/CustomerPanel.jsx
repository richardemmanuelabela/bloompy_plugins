// External Dependencies
import React, {Component, useEffect, useRef} from 'react';

// Internal Dependencies
import './style.css';
const $ = window.jQuery;

class CustomerPanel extends Component {

  static slug = 'booknetic_cp';

  render(props) {

    return (
        <Bpanel {...this.props} />
    );
  }

}

function Bpanel(props){

  const ref = useRef();

  const fetchView = async (shortcode)=>{
    let data = new FormData();
    data.append('shortcode',shortcode)
    let req = await fetch('/?bkntc_preview=1',{
      method:'POST',
      body:data
    });
    let res = await req.text();
    $(ref.current).html(res)
  }

  useEffect(()=>{
    fetchView('[booknetic-cp]')
  },[])

  return (
      <div style={{pointerEvents:"none"}} ref={ref}>
        Loading...
      </div>
  );
}


export default CustomerPanel;
